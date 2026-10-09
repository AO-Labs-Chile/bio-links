// PT:Kurage - Frontend Controller
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('search-form');
  const emailInput = document.getElementById('email-input');
  const btnSubmit = document.getElementById('btn-submit');
  const btnSubmitText = document.getElementById('btn-submit-text');
  
  const sectionForm = document.getElementById('section-form');
  const sectionSuccess = document.getElementById('section-success');
  const sectionNoCode = document.getElementById('section-no-code');
  const sectionNotFound = document.getElementById('section-not-found');
  
  const displayEmail = document.getElementById('display-email');
  const displayCode = document.getElementById('display-code');
  const btnCopy = document.getElementById('btn-copy');
  const btnResend = document.getElementById('btn-resend');
  const btnResendText = document.getElementById('btn-resend-text');
  const resendToast = document.getElementById('resend-toast');
  
  const btnReset = document.getElementById('btn-reset');
  const btnResetNoCode = document.getElementById('btn-reset-no-code');
  const btnResetNotFound = document.getElementById('btn-reset-not-found');

  let currentVerifiedEmail = '';
  let cooldownInterval = null;

  // Enviar formulario de búsqueda
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = emailInput.value.trim();

    if (!email) {
      emailInput.focus();
      return;
    }

    setLoading(true);
    hideAllResults();

    try {
      const formData = new FormData();
      formData.append('email', email);

      const res = await fetch('api.php?action=check', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();
      setLoading(false);

      if (data.found) {
        if (data.has_code) {
          currentVerifiedEmail = data.email;
          showSuccess(data);
        } else {
          showNoCode(data);
        }
      } else {
        showNotFound(data.message);
      }
    } catch (err) {
      setLoading(false);
      alert('Error de conexión al consultar el correo. Por favor intenta nuevamente.');
    }
  });

  function setLoading(loading) {
    btnSubmit.disabled = loading;
    if (loading) {
      btnSubmitText.innerHTML = '<span class="spinner"></span> Consultando...';
    } else {
      btnSubmitText.textContent = 'Consultar Beneficio';
    }
  }

  function hideAllResults() {
    sectionSuccess.style.display = 'none';
    if (sectionNoCode) sectionNoCode.style.display = 'none';
    sectionNotFound.style.display = 'none';
    resendToast.style.display = 'none';
  }

  function showSuccess(data) {
    if (displayEmail) displayEmail.textContent = data.email;
    if (displayCode && data.code) displayCode.textContent = data.code;

    // Detalles dinámicos según código
    const details = document.getElementById('display-discount-details');
    const ticketsBtn = document.getElementById('btn-tickets-link');
    const paisBtn = document.getElementById('display-pais-btn');

    if (details) {
      const pct = data.porcentaje || '35';
      const pais = data.pais || 'Chile';
      details.innerHTML = `Beneficio asignado: <strong>${pct}% de descuento</strong> para entrada General en <strong>${pais}</strong>.<br><small style="color: #94a3b8;">Válido únicamente el 03 de octubre de 2026.</small>`;
    }

    if (ticketsBtn && data.tickets_url) {
      ticketsBtn.href = data.tickets_url;
      if (paisBtn) paisBtn.textContent = (data.pais || '').toUpperCase();
      ticketsBtn.style.display = 'inline-flex';
    }

    sectionForm.style.display = 'none';
    sectionSuccess.style.display = 'block';

    // Reset botón de copiar si existe
    if (btnCopy) {
      btnCopy.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Copiar código`;
    }
    
    // Reset botón de reenvío
    btnResend.disabled = false;
    btnResendText.textContent = 'Reenviar código a mi correo';
  }

  function showNoCode(data) {
    sectionForm.style.display = 'none';
    if (sectionNoCode) sectionNoCode.style.display = 'block';

    const emailEl = document.getElementById('display-no-code-email');
    if (emailEl) emailEl.textContent = data.email;

    const countryBadge = document.getElementById('display-no-code-country');
    if (countryBadge) {
      if (data.country) {
        countryBadge.textContent = 'País: ' + data.country;
        countryBadge.style.display = 'inline-block';
      } else {
        countryBadge.style.display = 'none';
      }
    }
  }

  function showNotFound(msg) {
    sectionForm.style.display = 'none';
    sectionNotFound.style.display = 'block';
  }

  function resetForm() {
    hideAllResults();
    sectionForm.style.display = 'block';
    emailInput.value = '';
    emailInput.focus();
    currentVerifiedEmail = '';
    if (cooldownInterval) clearInterval(cooldownInterval);
  }

  btnReset.addEventListener('click', resetForm);
  if (btnResetNoCode) btnResetNoCode.addEventListener('click', resetForm);
  btnResetNotFound.addEventListener('click', resetForm);

  // Copiar código al portapapeles si existe el botón
  if (btnCopy && displayCode) {
    btnCopy.addEventListener('click', async () => {
      const code = displayCode.textContent.trim();
      if (!code) return;

      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(code);
        } else {
          const textarea = document.createElement('textarea');
          textarea.value = code;
          document.body.appendChild(textarea);
          textarea.select();
          document.execCommand('copy');
          document.body.removeChild(textarea);
        }
        btnCopy.innerHTML = `<svg width="14" height="14" fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg> ¡Copiado!`;
        setTimeout(() => {
          btnCopy.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> Copiar código`;
        }, 3000);
      } catch (err) {
        alert('Código: ' + code);
      }
    });
  }

  // Reenviar correo con el código
  btnResend.addEventListener('click', async () => {
    if (!currentVerifiedEmail) return;

    btnResend.disabled = true;
    btnResendText.innerHTML = '<span class="spinner"></span> Enviando correo...';
    resendToast.style.display = 'none';

    try {
      const formData = new FormData();
      formData.append('email', currentVerifiedEmail);

      const res = await fetch('api.php?action=resend', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        showToast(data.message, 'success');
        startCooldown(45);
      } else {
        showToast(data.error || 'No se pudo reenviar el correo.', 'error');
        btnResend.disabled = false;
        btnResendText.textContent = 'Reenviar código a mi correo';
      }
    } catch (err) {
      showToast('Error de red al intentar enviar el correo.', 'error');
      btnResend.disabled = false;
      btnResendText.textContent = 'Reenviar código a mi correo';
    }
  });

  function showToast(msg, type) {
    resendToast.textContent = msg;
    resendToast.className = 'toast-msg ' + type;
    resendToast.style.display = 'block';
  }

  function startCooldown(seconds) {
    let remaining = seconds;
    btnResend.disabled = true;

    cooldownInterval = setInterval(() => {
      remaining--;
      if (remaining > 0) {
        btnResendText.textContent = `Reenviar disponible en ${remaining}s`;
      } else {
        clearInterval(cooldownInterval);
        btnResend.disabled = false;
        btnResendText.textContent = 'Reenviar código a mi correo';
      }
    }, 1000);
  }
});
