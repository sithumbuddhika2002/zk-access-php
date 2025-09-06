<?php
return [
    'ip'       => '192.168.1.201',
    'port'     => 4370,
    // Controller communication password (leave empty if none set on the C3-200):
    'password' => '',
    // Buffers
    'rtlog_buffer' => 8192,   // bytes for real-time log
    'param_buffer' => 2048,   // bytes for device params
    // Path to the Pull SDK DLL
    'dll'      => __DIR__ . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'plcommpro.dll',
];
