const registerForm = document.getElementById('register-form');
const registerMessage = document.getElementById('register-message');

registerForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!registerForm.reportValidity()) {
    return;
  }

  const submitButton = registerForm.querySelector('button[type="submit"]');
  const formData = new FormData(registerForm);

  registerMessage.textContent = '';
  registerMessage.className = 'form-message';
  submitButton.disabled = true;
  submitButton.textContent = 'Creating account...';

  try {
    const response = await fetch('api/register.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        username: formData.get('username'),
        password: formData.get('password')
      })
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || 'Unable to create your account.');
    }

    registerForm.reset();
    registerMessage.textContent = 'Account created. You can now sign in.';
    registerMessage.classList.add('form-message--success');
  } catch (error) {
    registerMessage.textContent = error.message || 'Unable to create your account.';
    registerMessage.classList.add('form-message--error');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Register Account';
  }
});
