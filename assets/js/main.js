/**
 * Main JavaScript
 * CMS inline-redigering och interaktivitet
 */

'use strict';

// CMS Inline Editing
document.addEventListener('DOMContentLoaded', function() {
  initInlineEditing();
  initImageUpload();
});

/**
 * Initiera inline-redigering
 */
function initInlineEditing() {
  const editableElements = document.querySelectorAll('[contenteditable="true"]');
  
  editableElements.forEach(element => {
    const originalValue = element.textContent;
    
    // Spara vid blur
    element.addEventListener('blur', function() {
      const key = this.dataset.key;
      const value = this.textContent.trim();
      
      if (value !== originalValue) {
        saveContent(key, value);
      }
    });
    
    // Prevent line breaks i inline elements
    element.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && this.tagName !== 'DIV' && this.tagName !== 'P') {
        e.preventDefault();
        this.blur();
      }
    });
  });
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
