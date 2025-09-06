<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Students</title>
<style>
body{margin:0;font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;background:#fff}
.bar{display:flex;gap:10px;align-items:end;margin-bottom:14px}
input,select,button{padding:10px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px}
button{cursor:pointer}
table{width:100%;border-collapse:separate;border-spacing:0 8px}
th,td{text-align:left;padding:10px 12px}
th{color:#475569;font-size:12px;text-transform:uppercase}
tr{background:#f8fafc}
tr:hover{background:#eef2ff}
.actions button{margin-right:6px}
.pill{padding:4px 8px;border-radius:999px;font-size:12px}
.ok{background:#d1fae5}
.bad{background:#fee2e2}

/* ---------- Loader ---------- */
#loaderOverlay {
  display:none;
  position:fixed;
  top:0;left:0;width:100%;height:100%;
  background:rgba(255,255,255,0.7);
  z-index:9999;
  align-items:center;
  justify-content:center;
}
.spinner {
  border: 6px solid #e5e7eb;
  border-top: 6px solid #3b82f6;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  0% { transform: rotate(0deg);}
  100% { transform: rotate(360deg);}
}
</style>
</head>
<body>
<h2 style="margin:10px 0 16px 0;">Add / Manage Students</h2>

<div class="bar">
  <div><label>Student ID<br><input id="student_id"></label></div>
  <div><label>Full Name<br><input id="full_name"></label></div>
  <div><label>PIN (unique)<br><input id="pin"></label></div>
  <div><label>Card Number<br><input id="card_number"></label></div>
  <div>
    <label>Status<br>
      <select id="status">
        <option value="active" selected>active</option>
        <option value="inactive">inactive</option>
      </select>
    </label>
  </div>
  <div><button onclick="saveStudent()">Save to DB + Device</button></div>
</div>

<div id="msg"></div>

<table id="grid">
<thead>
<tr>
<th>#</th>
<th>Student ID</th>
<th>Full Name</th>
<th>PIN</th>
<th>Card</th>
<th>Status</th>
<th>Updated</th>
<th>Actions</th>
</tr>
</thead>
<tbody></tbody>
</table>

<!-- Loader Overlay -->
<div id="loaderOverlay"><div class="spinner"></div></div>

<script>
function el(id){return document.getElementById(id)}
function note(html){ el('msg').innerHTML=html; setTimeout(()=>el('msg').innerHTML='',4000)}

function showLoader(){ el("loaderOverlay").style.display="flex"; }
function hideLoader(){ el("loaderOverlay").style.display="none"; }

async function load(){
  showLoader();
  try {
    const r=await fetch('../api/students_list.php');
    const j=await r.json();
    const tb=document.querySelector('#grid tbody'); tb.innerHTML='';
    j.rows.forEach((row,i)=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`
        <td>${i+1}</td>
        <td>${row.student_id}</td>
        <td>${row.full_name}</td>
        <td>${row.pin}</td>
        <td>${row.card_number}</td>
        <td><span class="pill ${row.status==='active'?'ok':'bad'}">${row.status}</span></td>
        <td>${row.updated_at}</td>
        <td class="actions">
          <button onclick='editRow(${JSON.stringify(row)})'>Edit</button>
          <button onclick='delRow("${row.pin}")'>Delete</button>
          <button onclick='pushOnly("${row.pin}")'>Push to Device</button>
        </td>`;
      tb.appendChild(tr);
    });
  } catch(e){
    note("Load failed: "+e);
  } finally {
    hideLoader();
  }
}

function editRow(row){
  el('student_id').value=row.student_id;
  el('full_name').value=row.full_name;
  el('pin').value=row.pin;
  el('card_number').value=row.card_number;
  el('status').value=row.status;
}

function saveStudent() {
    const student = {
        student_id: el("student_id").value.trim(),
        full_name: el("full_name").value.trim(),
        pin: el("pin").value.trim(),
        card_number: el("card_number").value.trim(),
        status: el("status").value
    };
    console.log(student); // check values

    if (!student.student_id || !student.full_name || !student.pin || !student.card_number) {
        return note("All fields are required!");
    }

    showLoader();
    fetch("../api/students_save.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify(student)
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) note("Error: " + data.error);
        else note(data.message);
        load();
    })
    .catch(err => note("Fetch error: " + err))
    .finally(() => hideLoader());
}


async function delRow(pin){
  if(!confirm('Delete from DB and Device?')) return;
  showLoader();
  try{
    const r=await fetch('../api/students_delete.php?pin='+encodeURIComponent(pin));
    const j=await r.json();
    note(j.message||'Deleted');
    await load();
  }catch(e){
    note("Delete failed: "+e);
  }finally{
    hideLoader();
  }
}

async function pushOnly(pin){
  showLoader();
  try{
    const r=await fetch('../api/push_device.php?pin='+encodeURIComponent(pin));
    const j=await r.json();
    note(j.message||'Pushed to device');
  }catch(e){
    note("Push failed: "+e);
  }finally{
    hideLoader();
  }
}

load();
</script>
</body>
</html>
