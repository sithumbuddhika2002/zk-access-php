<?php
class ZKPull {
    private \FFI $lib;
    private int $handle = 0;

    public function __construct(string $dllPath) {
        if (!is_file($dllPath)) {
            throw new \RuntimeException("DLL not found: $dllPath");
        }
        $cdef = <<<CDEF
            typedef intptr_t HANDLE;
            HANDLE Connect(const char *Parameters);
            int Disconnect(HANDLE handle);
            int GetRTLog(HANDLE handle, char *Buffer, int BufferSize);
            int GetDeviceParam(HANDLE handle, char *Buffer, int BufferSize, const char *Items);
            int SetDeviceParam(HANDLE handle, const char *ItemValues);
            int PullLastError(void);

            int SetDeviceData(HANDLE handle, const char *TableName, const char *Data, const char *Options);
            int DeleteDeviceData(HANDLE handle, const char *TableName, const char *Data, const char *Options);
            int GetDeviceData(HANDLE handle, char *Buffer, int BufferSize,
                              const char *TableName, const char *FieldNames,
                              const char *Filter, const char *Options);
        CDEF;
        $this->lib = \FFI::cdef($cdef, $dllPath);
    }
    public function connect(string $ip, int $port = 4370, string $password = '', int $timeoutMs = 4000): void {
        $param = "protocol=TCP,ipaddress={$ip},port={$port},timeout={$timeoutMs},passwd={$password}";
        $h = $this->lib->Connect($param);
        if ($h === 0) {
            $err = $this->lib->PullLastError();
            throw new \RuntimeException("Connect failed (PullLastError=$err)");
        }
        $this->handle = $h;
    }

    public function disconnect(): void {
        if ($this->handle) {
            $this->lib->Disconnect($this->handle);
            $this->handle = 0;
        }
    }

public function getDeviceParam(array $items, int $bufSize = 2048): string {
    $buf = $this->lib->new("char[$bufSize]", false);  // ✅ FIXED
    $list = implode(',', $items);
    $ret = $this->lib->GetDeviceParam($this->handle, $buf, $bufSize, $list);
    if ($ret < 0) {
        $err = $this->lib->PullLastError();
        throw new \RuntimeException("GetDeviceParam failed (PullLastError=$err)");
    }
    return \FFI::string($buf);
}
    public function setDeviceParam(array $kvPairs): void {
        $items = [];
        foreach ($kvPairs as $k => $v) { $items[] = "{$k}={$v}"; }
        $str = implode(',', $items);
        $ret = $this->lib->SetDeviceParam($this->handle, $str);
        if ($ret < 0) {
            $err = $this->lib->PullLastError();
            throw new \RuntimeException("SetDeviceParam failed (PullLastError=$err)");
        }
    }

    // ✅ tiny fix: allocate with FFI::new; no signature change
public function getRTLog(int $bufSize = 4096): string {
    $buf = $this->lib->new("char[$bufSize]", false);  // ✅ FIXED
    $ret = $this->lib->GetRTLog($this->handle, $buf, $bufSize);
    if ($ret > 0) {
        return \FFI::string($buf);
    }
    return '';
}
    // ---------- Device data helpers (non-breaking additions) ----------

    private function _setDeviceData(string $table, string $data, string $options = ""): int {
        $ret = $this->lib->SetDeviceData($this->handle, $table, $data, $options);
        if ($ret < 0) {
            $err = $this->lib->PullLastError();
            throw new \RuntimeException("SetDeviceData failed (PullLastError=$err) table=$table data=$data");
        }
        return $ret;
    }

    private function _deleteDeviceData(string $table, string $data, string $options = ""): int {
        $ret = $this->lib->DeleteDeviceData($this->handle, $table, $data, $options);
        if ($ret < 0) {
            $err = $this->lib->PullLastError();
            throw new \RuntimeException("DeleteDeviceData failed (PullLastError=$err) table=$table data=$data");
        }
        return $ret;
    }

private function _getDeviceData(
    string $table, string $fields = "*", string $filter = "",
    int $bufSize = 65536, string $options = ""
): string {
    $buf = $this->lib->new("char[$bufSize]", false);  // ✅ FIXED
    $ret = $this->lib->GetDeviceData($this->handle, $buf, $bufSize, $table, $fields, $filter, $options);
    if ($ret < 0) {
        $err = $this->lib->PullLastError();
        throw new \RuntimeException("GetDeviceData failed (PullLastError=$err)");
    }
    return \FFI::string($buf);
}

    // ---------- Public API for your web UI ----------

    /** Create or update a user with card */
    public function upsertUser(string $pin, string $cardNo, string $name, string $group = "1"): void {
        $data = "Pin={$pin}\tCardNo={$cardNo}\tName={$name}\tGroup={$group}\tStartTime=0\tEndTime=0";
        $this->_setDeviceData("user", $data, "");
    }

    /** Delete user by PIN (and card association) */
    public function deleteUserByPin(string $pin): void {
        $this->_deleteDeviceData("user", "Pin={$pin}", "");
    }

    /** Change/assign card for an existing PIN */
    public function setUserCard(string $pin, string $cardNo): void {
        $data = "Pin={$pin}\tCardNo={$cardNo}";
        $this->_setDeviceData("user", $data, "");
    }

    /** Fetch a user row (string result from device) */
    public function getUserByPin(string $pin): array {
        $raw = $this->_getDeviceData("user", "Pin,CardNo,Name,Group", "Pin={$pin}");
        if (trim($raw) === '') return [];
        $rows = [];
        foreach (explode("\n", trim($raw)) as $line) {
            $row = [];
            foreach (explode("\t", $line) as $kv) {
                if (strpos($kv, "=") !== false) {
                    [$k,$v] = explode("=", $kv, 2);
                    $row[$k] = $v;
                }
            }
            if ($row) $rows[] = $row;
        }
        return $rows[0] ?? [];
    }

    // (Your earlier convenience method can call the new one)
    public function addUser($pin, $cardNo, $name) {
        $this->upsertUser($pin, $cardNo, $name);
        return 1;
    }
    
}
