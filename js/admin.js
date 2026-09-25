const usersBody = document.querySelector('.data-table tbody');
const usersMessage = document.getElementById('users-message');
const signOutButton = document.getElementById('sign-out-button');
const resetDialog = document.getElementById('reset-password-dialog');
const resetForm = document.getElementById('reset-password-form');
const resetUserLabel = document.getElementById('reset-password-user');
const newPasswordInput = document.getElementById('new-password');
const cancelResetButton = document.getElementById('cancel-reset-button');

let resetUserId = null;

function showTableMessage(message) {
  usersBody.replaceChildren();
  const row = document.createElement('tr');
  const cell = document.createElement('td');
  cell.colSpan = 7;
  cell.textContent = message;
  row.append(cell);
  usersBody.append(row);
}

function setMessage(message, isError = false) {
  usersMessage.textContent = message;
  usersMessage.className = `form-message${isError ? ' form-message--error' : ' form-message--success'}`;
}

function formatDate(value) {
  return value ? value.replace('T', ' ') : '';
}

function createButton(label, className, handler) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = className;
  button.textContent = label;
  button.addEventListener('click', handler);
  return button;
}

function renderUsers(users, currentUserId) {
  usersBody.replaceChildren();

  if (users.length === 0) {
    showTableMessage('No users found.');
    return;
  }

  users.forEach((user) => {
    const row = document.createElement('tr');
    [user.ID, user.FirstName, user.LastName, user.Username, formatDate(user.DateCreated), formatDate(user.DateUpdated)]
      .forEach((value) => {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.append(cell);
      });

    const actions = document.createElement('td');
    actions.className = 'table-actions';
    const resetButton = createButton('Reset password', 'button button--small', () => openResetDialog(user));
    actions.append(resetButton);

    if (Number(user.ID) === Number(currentUserId)) {
      const currentAccount = document.createElement('span');
      currentAccount.className = 'account-status';
      currentAccount.textContent = 'Current account';
      actions.append(currentAccount);
    } else if (Number(user.IsDisabled) === 1) {
      const disabled = document.createElement('span');
      disabled.className = 'account-status';
      disabled.textContent = 'Disabled';
      actions.append(disabled);
      actions.append(createButton('Enable', 'button button--small button--enable', () => enableUser(user, row)));
    } else {
      actions.append(createButton('Disable', 'button button--small button--danger', () => disableUser(user, row)));
    }

    row.append(actions);
    usersBody.append(row);
  });
}

async function request(url, body) {
  const response = await fetch(url, {
    method: body ? 'POST' : 'GET',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}) },
    ...(body ? { body: JSON.stringify(body) } : {})
  });
  const result = await response.json();

  if (response.status === 401 || response.status === 403) {
    window.location.assign('index.html');
    return null;
  }
  if (!response.ok) {
    throw new Error(result.error || 'Request failed.');
  }
  return result;
}

async function loadUsers() {
  try {
    const result = await request('api/adminUsers.php');
    if (result) renderUsers(result.users, result.currentUserId);
  } catch (error) {
    showTableMessage('Unable to load users.');
    setMessage(error.message || 'Unable to load users.', true);
  }
}

function openResetDialog(user) {
  resetUserId = user.ID;
  resetUserLabel.textContent = `Set a new password for ${user.Username}.`;
  resetForm.reset();
  resetDialog.showModal();
  newPasswordInput.focus();
}

async function disableUser(user, row) {
  if (!window.confirm(`Disable ${user.Username}? They will no longer be able to sign in.`)) return;

  const button = row.querySelector('.button--danger');
  button.disabled = true;
  button.textContent = 'Disabling...';

  try {
    const result = await request('api/disableUser.php', { userId: user.ID });
    if (!result) return;
    setMessage(result.message);
    await loadUsers();
  } catch (error) {
    button.disabled = false;
    button.textContent = 'Disable';
    setMessage(error.message || 'Unable to disable user.', true);
  }
}

async function enableUser(user, row) {
  if (!window.confirm(`Enable ${user.Username}? They will be able to sign in again.`)) return;

  const button = row.querySelector('.button--enable');
  button.disabled = true;
  button.textContent = 'Enabling...';

  try {
    const result = await request('api/enableUser.php', { userId: user.ID });
    if (!result) return;
    setMessage(result.message);
    await loadUsers();
  } catch (error) {
    button.disabled = false;
    button.textContent = 'Enable';
    setMessage(error.message || 'Unable to enable user.', true);
  }
}

resetForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!resetForm.reportValidity()) return;

  const submitButton = resetForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.textContent = 'Resetting...';

  try {
    const result = await request('api/changePassword.php', { userId: resetUserId, newPassword: newPasswordInput.value });
    if (!result) return;
    resetDialog.close();
    setMessage(result.message);
  } catch (error) {
    setMessage(error.message || 'Unable to reset password.', true);
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Reset password';
  }
});

cancelResetButton.addEventListener('click', () => resetDialog.close());

signOutButton.addEventListener('click', async () => {
  signOutButton.disabled = true;
  signOutButton.textContent = 'Signing out...';
  try {
    await fetch('api/logout.php', { method: 'POST', credentials: 'same-origin' });
  } finally {
    window.location.replace('index.html');
  }
});

loadUsers();
