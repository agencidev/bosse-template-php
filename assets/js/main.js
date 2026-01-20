/**
 * Main JavaScript
 * CMS inline-redigering och interaktivitet - WordPress-liknande
 */

'use strict';

// CMS State
let currentlyEditing = null;
let originalValue = null;

// CMS Inline Editing
document.addEventListener('DOMContentLoaded', function() {
  initInlineEditing();
  initImageUpload();
});

/**
 * Initiera inline-redigering
 */
function initInlineEditing() {
  const editableElements = document.querySelectorAll('.cms-editable');
  
  editableElements.forEach(element => {
    element.addEventListener('click', function(e) {
      e.stopPropagation();
      startEditing(this);
    });
  });
  
  // Stäng redigering vid klick utanför
  document.addEventListener('click', function(e) {
    if (currentlyEditing && !e.target.closest('.cms-edit-wrapper')) {
      cancelEditing();
    }
  });
}

/**
 * Starta redigering av element
 */
function startEditing(element) {
  if (currentlyEditing) {
    cancelEditing();
  }
  
  currentlyEditing = element;
  originalValue = element.textContent;
  
  const key = element.dataset.key;
  const tag = element.dataset.tag;
  const isMultiline = tag === 'p' || tag === 'div' || tag === 'textarea';
  
  // Skapa redigeringsformulär
  const wrapper = document.createElement('div');
  wrapper.className = 'cms-edit-wrapper';
  
  const inputElement = isMultiline 
    ? document.createElement('textarea')
    : document.createElement('input');
  
  if (!isMultiline) {
    inputElement.type = 'text';
  }
  
  inputElement.value = element.textContent;
  inputElement.className = 'cms-edit-input';
  
  if (isMultiline) {
    inputElement.rows = 4;
  }
  
  // Skapa knappar
  const buttons = document.createElement('div');
  buttons.className = 'cms-edit-buttons';
  
  const saveBtn = document.createElement('button');
  saveBtn.textContent = '✓ Spara';
  saveBtn.className = 'cms-btn cms-btn-save';
  saveBtn.onclick = () => saveEditing(element, inputElement.value);
  
  const cancelBtn = document.createElement('button');
  cancelBtn.textContent = '✕ Avbryt';
  cancelBtn.className = 'cms-btn cms-btn-cancel';
  cancelBtn.onclick = () => cancelEditing();
  
  buttons.appendChild(saveBtn);
  buttons.appendChild(cancelBtn);
  
  wrapper.appendChild(inputElement);
  wrapper.appendChild(buttons);
  
  // Ersätt element med redigeringsformulär
  element.style.display = 'none';
  element.parentNode.insertBefore(wrapper, element.nextSibling);
  
  inputElement.focus();
  if (!isMultiline) {
    inputElement.select();
  }
  
  // Keyboard shortcuts
  inputElement.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !isMultiline) {
      e.preventDefault();
      saveEditing(element, inputElement.value);
    }
    if (e.key === 'Escape') {
      cancelEditing();
    }
  });
}

/**
 * Spara redigering
 */
function saveEditing(element, newValue) {
  const key = element.dataset.key;
  
  if (newValue.trim() !== originalValue.trim()) {
    saveContent(key, newValue);
    element.textContent = newValue;
  }
  
  cleanupEditing();
}

/**
 * Avbryt redigering
 */
function cancelEditing() {
  if (currentlyEditing) {
    currentlyEditing.textContent = originalValue;
  }
  cleanupEditing();
}

/**
 * Rensa redigeringsformulär
 */
function cleanupEditing() {
  if (currentlyEditing) {
    const wrapper = currentlyEditing.parentNode.querySelector('.cms-edit-wrapper');
    if (wrapper) {
      wrapper.remove();
    }
    currentlyEditing.style.display = '';
    currentlyEditing = null;
    originalValue = null;
  }
}

/**
 * Spara innehåll via API
 */
async function saveContent(key, value) {
  try {
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    
    const response = await fetch('/cms/api.php?action=update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        key: key,
        value: value,
        csrf_token: csrfToken
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      showNotification('Sparat!', 'success');
    } else {
      showNotification('Fel vid sparning: ' + data.error, 'error');
    }
  } catch (error) {
    showNotification('Fel vid sparning', 'error');
    console.error('Save error:', error);
  }
}

/**
 * Initiera bilduppladdning
 */
function initImageUpload() {
  // Skapa dold file input
  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.accept = 'image/*';
  fileInput.style.display = 'none';
  document.body.appendChild(fileInput);
  
  window.currentImageKey = null;
  
  fileInput.addEventListener('change', async function() {
    if (this.files.length > 0 && window.currentImageKey) {
      await uploadImage(window.currentImageKey, this.files[0]);
    }
  });
  
  window.uploadImageInput = fileInput;
}

/**
 * Ladda upp bild
 */
window.uploadImage = function(key) {
  window.currentImageKey = key;
  window.uploadImageInput.click();
};

async function uploadImage(key, file) {
  const formData = new FormData();
  formData.append('image', file);
  formData.append('key', key);
  
  const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
  if (csrfToken) {
    formData.append('csrf_token', csrfToken);
  }
  
  try {
    showNotification('Laddar upp...', 'info');
    
    const response = await fetch('/cms/api.php?action=upload', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Uppdatera bilden
      const imageWrapper = document.querySelector(`[data-key="${key}"]`);
      if (imageWrapper) {
        const img = imageWrapper.querySelector('img');
        if (img) {
          img.src = data.url + '?v=' + Date.now(); // Cache bust
        }
      }
      
      showNotification('Bild uppladdad!', 'success');
    } else {
      showNotification('Fel vid uppladdning: ' + data.error, 'error');
    }
  } catch (error) {
    showNotification('Fel vid uppladdning', 'error');
    console.error('Upload error:', error);
  }
}

/**
 * Visa notifikation
 */
function showNotification(message, type = 'info') {
  // Ta bort befintliga notifikationer
  const existing = document.querySelector('.notification');
  if (existing) {
    existing.remove();
  }
  
  const notification = document.createElement('div');
  notification.className = `notification notification--${type}`;
  notification.textContent = message;
  
  // Styling
  Object.assign(notification.style, {
    position: 'fixed',
    top: '20px',
    right: '20px',
    padding: '1rem 1.5rem',
    borderRadius: '0.5rem',
    backgroundColor: type === 'success' ? 'var(--color-success)' : 
                     type === 'error' ? 'var(--color-error)' : 
                     'var(--color-info)',
    color: 'white',
    fontWeight: '500',
    boxShadow: 'var(--shadow-lg)',
    zIndex: '9999',
    animation: 'slideIn 0.3s ease-out'
  });
  
  document.body.appendChild(notification);
  
  // Ta bort efter 3 sekunder
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
  
  .editable {
    outline: 2px dashed var(--color-primary);
    outline-offset: 4px;
    cursor: text;
  }
  
  .editable:hover {
    outline-color: var(--color-primary-dark);
  }
  
  .editable-image-wrapper {
    position: relative;
    display: inline-block;
  }
  
  .image-upload-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 0.5rem 1rem;
    background-color: var(--color-primary);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    opacity: 0;
    transition: opacity var(--transition-fast);
  }
  
  .editable-image-wrapper:hover .image-upload-btn {
    opacity: 1;
  }
  
  .image-upload-btn:hover {
    background-color: var(--color-primary-dark);
  }
`;
document.head.appendChild(style);
