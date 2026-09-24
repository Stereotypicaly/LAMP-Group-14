const loginForm = document.getElementById('login-form');
const loginMessage = document.getElementById('login-message');

loginForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!loginForm.reportValidity()) {
    return;
  }

  const submitButton = loginForm.querySelector('button[type="submit"]');
  const formData = new FormData(loginForm);

  loginMessage.textContent = '';
  loginMessage.className = 'form-message';
  submitButton.disabled = true;
  submitButton.textContent = 'Signing in...';

  try {
    const response = await fetch('api/login.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        username: formData.get('username'),
        password: formData.get('password')
      })
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || 'Unable to sign in.');
    }

    window.location.assign(result.isAdmin ? 'admin.html' : 'user.html');
  } catch (error) {
    loginMessage.textContent = error.message || 'Unable to sign in.';
    loginMessage.classList.add('form-message--error');
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = 'Sign in';
  }
});
