<?php
// ===============================
// FETCH TEAM + DOL DATA
// ===============================
$teamData = [];
$result = $conn->query("
  SELECT emp_id, emp_name, role, audit_type, emp_dol
  FROM engagement_team
  WHERE eng_id = " . intval($eng['eng_id'])
);

while ($row = $result->fetch_assoc()) {
  $roleType = strtolower($row['role']);
  if ($roleType === 'manager') continue;

  $empName = trim($row['emp_name'] ?? '');
  if ($empName === '') continue;

  $auditTypeString = trim($row['audit_type'] ?? '');
  $parts = explode(' ', $auditTypeString);
  $auditKey = count($parts) >= 2 ? $parts[0] . ' ' . $parts[1] : '';

  // if ($auditKey === '') continue;

  $teamData[$row['emp_id']]['emp_id'] = $row['emp_id'];
  $teamData[$row['emp_id']]['name']   = $empName;
  $teamData[$row['emp_id']]['type']   = $roleType;
  $teamData[$row['emp_id']][$auditKey] = $row['emp_dol'];
}

$dolData = array_values($teamData);
?>

<script>

document.querySelectorAll('.delete-team-member').forEach(btn => {
  btn.addEventListener('click', async e => {
    const card = e.target.closest('.card');
    const name = card.getAttribute('data-name');

    if (!confirm(`Are you sure you want to delete ${name}?`)) return;

    const formData = new FormData();
    formData.append('eng_id', "<?php echo $eng['eng_id']; ?>");
    formData.append('name', name);

    try {
      const res = await fetch('../includes/delete_team_member.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) card.remove();
      else alert('Failed to delete: ' + (data.error || 'Unknown error'));
    } catch (err) {
      console.error(err);
      alert('Failed to delete: network or server error.');
    }
  });
});


  document.addEventListener('DOMContentLoaded', () => {

  const engId = "<?php echo $eng['eng_id']; ?>";

  // ===============================
  // PHP-provided DOL + all team members
  // ===============================
  const existingDOLData = <?php echo json_encode($dolData); ?>;

  const seniorsContainer = document.getElementById('seniorsContainer');
  const staffContainer   = document.getElementById('staffContainer');
  const managerContainer = document.getElementById('managerContainer');
  const dolButtonsContainer = document.getElementById('dolButtonsContainer');

  const addSeniorBtn  = document.getElementById('addSeniorBtn');
  const addStaffBtn   = document.getElementById('addStaffBtn');
  const addManagerBtn = document.getElementById('addManagerBtn');

  let tempIdCounter = -1; // For new DOM-only members

  // ===============================
  // Merge team members (DOL + DOM) without duplicates
  // ===============================
  function getTeamMembers() {
    const membersMap = new Map();

    const addMember = (empId, name, type) => {
        if (!name || !type) return;
        if (empId === undefined || empId === null) return;

        const typeLower = type.toLowerCase();

        if (!membersMap.has(empId)) {
            membersMap.set(empId, {
                emp_id: empId,
                name,
                type: typeLower,
                role: type.charAt(0).toUpperCase() + type.slice(1)
            });
        }
    };

    existingDOLData.forEach(row => addMember(row.emp_id, row.name, row.type));

    return Array.from(membersMap.values()).sort((a, b) => {
        if (a.type === b.type) return a.name.localeCompare(b.name);
        return a.type === 'senior' ? -1 : 1;
    });
  }

  const rawAuditTypes = <?php echo json_encode(explode(',', $eng['eng_audit_type'] ?? '')); ?>;
  const auditTypes = Array.from(new Set(
    rawAuditTypes
      .map(t => t.trim().toUpperCase())
      .filter(t => t.startsWith('SOC'))
      .map(t => t.startsWith('SOC 1') ? 'SOC 1' : 'SOC 2')
  ));

  // ===============================
  // DOL BUTTON
  // ===============================
  function updateDOLButtons() {
    dolButtonsContainer.innerHTML = '';
    const members = getTeamMembers().filter(m => m.type === 'senior' || m.type === 'staff');
    if (!members.length) return;

    const btn = document.createElement('a');
    btn.href = 'javascript:void(0)';
    btn.className = 'text-decoration-none text-warning fw-semibold';
    btn.innerHTML = '<i class="bi bi-diagram-3 me-1"></i> DOL';
    btn.addEventListener('click', openDOLModal);

    dolButtonsContainer.appendChild(btn);
  }

  function getExistingDOL(empId, auditType) {
    const row = existingDOLData.find(r => r.emp_id == empId);
    return row && row[auditType] ? row[auditType] : '';
  }

  function openDOLModal() {
  const modalBody = document.getElementById('dolModalBody');
  modalBody.innerHTML = '';

  // HEADER
  const header = document.createElement('div');
  header.className = 'mb-3 p-4 border rounded';
  header.style.background = 'rgb(240,246,254)';
  header.innerHTML = `
    <div class="fw-bold mb-2">Audit Types</div>
    ${auditTypes.map(a => `<span class="badge me-2" style="background:#425cd5">${a}</span>`).join('')}
    <div class="mt-2 text-muted" style="font-size:13px">
      ${auditTypes.includes('SOC 1') ? 'Use CO prefix for SOC 1 (e.g., CO1, CO2)' : ''}
      ${auditTypes.includes('SOC 1') && auditTypes.includes('SOC 2') ? ' • ' : ''}
      ${auditTypes.includes('SOC 2') ? 'Use CC prefix for SOC 2 (e.g., CC1, CC2)' : ''}
    </div>
  `;
  modalBody.appendChild(header);

  // Deduplicate members by name + eng_id
  const membersMap = new Map();
  existingDOLData.forEach(row => {
    const key = row.name + '_' + row.eng_id;

    if (!membersMap.has(key)) {
      membersMap.set(key, {
        emp_id: row.emp_id,
        name: row.name,
        type: row.type.toLowerCase(),
        role: row.type.charAt(0).toUpperCase() + row.type.slice(1),
        dols: {} // store all audit DOLs here
      });
    }

    // Add DOLs for this audit type
    auditTypes.forEach(audit => {
      if (row[audit]) {
        membersMap.get(key).dols[audit] = row[audit];
      }
    });
  });

  // Only consider senior and staff
  let members = Array.from(membersMap.values())
    .filter(m => m.type === 'senior' || m.type === 'staff');

  // SORT: seniors first, then staff
  members.sort((a, b) => {
    if (a.type === b.type) return a.name.localeCompare(b.name); // optional: alphabetically
    return a.type === 'senior' ? -1 : 1;
  });

  if (!members.length) {
    modalBody.innerHTML += `<div class="text-muted">No team members found.</div>`;
    return;
  }

  // Render cards
  members.forEach(member => {
    const isSenior = member.type === 'senior';
    const card = document.createElement('div');
    card.className = 'mb-3 p-3 border rounded';
    card.style.background = isSenior ? '#f6f0ff' : '#f0fbf4';

    const empIdForInput = member.emp_id || tempIdCounter--;

    let inputs = '';
    auditTypes.forEach(audit => {
      const dolValue = member.dols[audit] || '';
      inputs += `
        <div class="mb-2">
          <label class="form-label small text-muted">${audit} Division of Labor</label>
          <input type="text" class="form-control"
            name="dol[${empIdForInput}][${audit}]"
            value="${dolValue}">
        </div>
      `;
    });

    card.innerHTML = `<div class="fw-bold mb-2">${member.role}: ${member.name}</div>${inputs}`;
    modalBody.appendChild(card);
  });

  new bootstrap.Modal(document.getElementById('dolModal')).show();
}



  // ===============================
  // SAVE DOL
  // ===============================
  document.getElementById('dolForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('eng_id', engId);

    try {
      const response = await fetch('../includes/save_dol.php', { method: 'POST', body: formData });
      const text = await response.text();
      let data = JSON.parse(text);

      if (data.success) location.reload();
      else alert('Save failed: ' + (data.error || 'Unknown error'));
    } catch (err) {
      console.error(err);
      alert('Save failed: network or server error.');
    }
  });

  // ===============================
  // TEAM BUTTONS (Add Manager / Senior / Staff)
  // ===============================
  function getNextIndex(container) {
    const cards = container.querySelectorAll('.card');
    return cards.length < 5 ? cards.length + 1 : null; // I changed to 5 (2/23/26)
  }

  function updateButtons() {
    addSeniorBtn.style.display = getNextIndex(seniorsContainer) ? 'inline-block' : 'none';
    addStaffBtn.style.display  = getNextIndex(staffContainer) ? 'inline-block' : 'none';
    addManagerBtn.style.display = managerContainer.querySelector('.card') ? 'none' : 'inline-block';
  }

  function addTeamMember(type, container) {
  const nextIndex = getNextIndex(container);
  if (!nextIndex) return;

  const roleName = type.charAt(0).toUpperCase() + type.slice(1);

  // Create outer card with matching styles
  const card = document.createElement('div');
  card.className = 'card mb-4';
  card.setAttribute('data-index', nextIndex);

  // Apply colors dynamically based on type
  switch(type) {
    case 'manager':
      card.style.borderColor = 'rgb(190,215,252)';
      card.style.borderRadius = '20px';
      card.style.backgroundColor = 'rgb(230,240,252)';
      break;
    case 'senior':
      card.style.borderColor = 'rgb(228,209,253)';
      card.style.borderRadius = '20px';
      card.style.backgroundColor = 'rgb(242,235,253)';
      break;
    case 'staff':
      card.style.borderColor = 'rgb(198,246,210)';
      card.style.borderRadius = '20px';
      card.style.backgroundColor = 'rgb(234,252,239)';
      break;
  }

  // Card body
  const body = document.createElement('div');
  body.className = 'card-body p-3';

  // Label div (role + index)
  const labelDiv = document.createElement('div');
  labelDiv.className = 'd-flex align-items-center mb-3';

  const label = document.createElement('h6');
  label.className = 'mb-0 text-uppercase';
  label.style.fontWeight = '600';
  label.style.fontSize = '12px';
  // Color per type
  if (type === 'manager') label.style.color = 'rgb(21,87,242)';
  if (type === 'senior') label.style.color = 'rgb(123,0,240)';
  if (type === 'staff') label.style.color = 'rgb(69,166,81)';
  label.textContent = type === 'manager' ? 'Manager' : `${roleName} ${nextIndex}`;

  labelDiv.appendChild(label);

  // Name input styled like the card's h6
  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'form-control';
  input.style.fontSize = '14px';
  input.style.fontWeight = '400';
  input.style.color = type === 'manager' ? 'rgb(0,37,132)' : type === 'senior' ? 'rgb(74,0,133)' : 'rgb(0,42,0)';
  input.placeholder = `Enter ${roleName} Name`;

  // Append label and input to body
  body.appendChild(labelDiv);
  body.appendChild(input);

  card.appendChild(body);
  container.appendChild(card);

  updateButtons();

  // Auto-focus input
  input.focus();

  // Save to DB when pressing Enter
  input.addEventListener('keydown', async e => {
    if (e.key === 'Enter') {
      const name = input.value.trim();
      if (!name) return;

      const formData = new FormData();
      formData.append('eng_id', engId);
      formData.append('type', type);
      formData.append('name', name);

      try {
        const res = await fetch('../includes/save_team_member.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          location.reload();
        } else {
          alert('Failed to add ' + roleName + ': ' + (data.error || 'Unknown error'));
        }
      } catch (err) {
        console.error(err);
        alert('Failed to add ' + roleName + ': network or server error.');
      }
    }
  });
}


  addSeniorBtn.addEventListener('click', () => addTeamMember('senior', seniorsContainer));
  addStaffBtn.addEventListener('click', () => addTeamMember('staff', staffContainer));
  addManagerBtn.addEventListener('click', () => addTeamMember('manager', managerContainer));

  // Initial setup
  updateButtons();
  updateDOLButtons();

  console.log('Audit Types:', auditTypes);
  console.log('Team Members:', getTeamMembers());

});
</script>