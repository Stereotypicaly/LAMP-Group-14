const createUserForm = document.getElementById('create-user-form');
const createUserMessage = document.getElementById('create-user-message');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm-password');
const isAdminInput = document.getElementById('is-admin');

function validatePasswordConfirmation() {
  const message = passwordInput.value === confirmPasswordInput.value ? '' : 'Passwords do not match.';
  confirmPasswordInput.setCustomValidity(message);
}

passwordInput.addEventListener('input', validatePasswordConfirmation);
confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);

createUserForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  validatePasswordConfirmation();

  if (!createUserForm.reportValidity()) return;

  const submitButton = createUserForm.querySelector('button[type="submit"]');
  const formData = new FormData(createUserForm);
  createUserMessage.textContent = '';
  createUserMessage.className = 'form-message';
  submitButton.disabled = true;
  submitButton.textContent = 'Creating user...';

  try {
    const response = await fetch('api/createUser.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        username: formData.get('username'),
        password: formData.get('password'),
        role: isAdminInput.checked ? 'admin' : 'user'
      })
    });
    const result = await response.json();

    if (response.status === 401 || response.status === 403) {
      window.location.assign('index.html');
      return;
    }
    if (!response.ok) throw new Error(result.error || 'Unable to create user.');

    window.location.assign('admin.html');
  } catch (error) {
    createUserMessage.textContent = error.message || 'Unable to create user.';
    createUserMessage.classList.add('form-message--error');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Create user';
  }
});
