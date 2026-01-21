/**
 * CMS JavaScript - EXAKT som Next.js-versionen i Bosse Portal
 * Inline-redigering med samma UX som React-komponenterna
 */

'use strict';

// Initialize CMS when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  initEditableElements();
  initImageUpload();
});

// Listen for edit mode changes
window.addEventListener('cms-edit-mode-changed', function(e) {
  updateEditableElements(e.detail.isEditMode);
});

/**
 * Initialize all editable elements
 */
function initEditableElements() {
  const editableTexts = document.querySelectorAll('[data-editable-text]');
  const editableImages = document.querySelectorAll('[data-editable-image]');
  
  editableTexts.forEach(element => {
    setupEditableText(element);
  });
  
  editableImages.forEach(element => {
    setupEditableImage(element);
  });
}

/**
 * Update editable elements when edit mode changes
 */
function updateEditableElements(isEditMode) {
  const editableTexts = document.querySelectorAll('[data-editable-text]');
  
  editableTexts.forEach(element => {
    if (isEditMode) {
      element.classList.add('cms-editable-active');
    } else {
      element.classList.remove('cms-editable-active');
    }
  });
}

/**
 * Setup editable text element - EXAKT som EditableText.jsx
 */
function setupEditableText(element) {
  const contentKey = element.dataset.contentKey;
  const field = element.dataset.field;
  const defaultValue = element.dataset.defaultValue || '';
  const tag = element.tagName.toLowerCase();
  
  let originalValue = element.textContent;
  let isEditing = false;
  
  // Click handler - only works when edit mode is active
  element.addEventListener('click', function(e) {
    if (!window.CMS || !window.CMS.isEditMode) return;
    if (isEditing) return;
    
    e.stopPropagation();
    startEditing();
  });
  
  function startEditing() {
    isEditing = true;
    originalValue = element.textContent;
    
    const isMultiline = tag === 'p' || tag === 'div' || tag === 'textarea';
    
    // Create wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'relative';
    
    // Create input element
    const inputElement = isMultiline 
      ? document.createElement('textarea')
      : document.createElement('input');
    
    if (!isMultiline) {
      inputElement.type = 'text';
    }
    
    inputElement.value = element.textContent;
    inputElement.className = 'border-2 border-persimmon bg-white text-woodsmoke rounded-lg p-4 w-full resize-none shadow-xl font-inherit text-inherit';
    inputElement.style.fontSize = 'inherit';
    inputElement.style.lineHeight = 'inherit';
    inputElement.style.fontWeight = 'inherit';
    
    if (isMultiline) {
      inputElement.rows = 4;
    }
    
    // Create buttons container
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'absolute -bottom-14 left-0 flex gap-2 bg-woodsmoke rounded-lg shadow-2xl p-3 z-50';
    
    // Save button
    const saveBtn = document.createElement('button');
    saveBtn.textContent = '✓ Spara';
    saveBtn.className = 'px-4 py-2 bg-persimmon text-white text-sm font-semibold rounded hover:bg-persimmon/90 disabled:opacity-50 transition';
    saveBtn.onclick = async function() {
      saveBtn.disabled = true;
      saveBtn.textContent = 'Sparar...';
      await handleSave(inputElement.value);
    };
    
    // Cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = '✕ Avbryt';
    cancelBtn.className = 'px-4 py-2 bg-white text-woodsmoke text-sm font-semibold rounded hover:bg-neutral-100 transition';
    cancelBtn.onclick = function() {
      handleCancel();
    };
    
    buttonsDiv.appendChild(saveBtn);
    buttonsDiv.appendChild(cancelBtn);
    
    wrapper.appendChild(inputElement);
    wrapper.appendChild(buttonsDiv);
    
    // Replace element with input
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
        saveBtn.click();
      }
      if (e.key === 'Escape') {
        handleCancel();
      }
    });
    
    async function handleSave(newValue) {
      try {
        // Fetch current content
        const res = await fetch(`/cms/api.php?action=get&_t=${Date.now()}`);
        const currentData = await res.json();
        
        // Update section
        const updatedSection = {
          ...(currentData[contentKey] || {}),
          [field]: newValue
        };
        
        // Save to server
        const saveRes = await fetch('/cms/api.php?action=update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            key: contentKey,
            value: updatedSection
          })
        });
        
        if (saveRes.ok) {
          element.textContent = newValue;
          showNotification('Sparat!', 'success');
        } else {
          showNotification('Kunde inte spara', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        showNotification('Kunde inte spara', 'error');
      } finally {
        cleanup();
      }
    }
    
    function handleCancel() {
      element.textContent = originalValue;
      cleanup();
    }
    
    function cleanup() {
      wrapper.remove();
      element.style.display = '';
      isEditing = false;
    }
  }
}

/**
 * Setup editable image element
 */
function setupEditableImage(element) {
  const contentKey = element.dataset.contentKey;
  const field = element.dataset.field;
  
  // Add overlay on hover when edit mode is active
  const wrapper = element.parentElement;
  if (!wrapper.classList.contains('cms-image-wrapper')) {
    const newWrapper = document.createElement('div');
    newWrapper.className = 'cms-image-wrapper';
    newWrapper.dataset.contentKey = contentKey;
    newWrapper.dataset.field = field;
    element.parentNode.insertBefore(newWrapper, element);
    newWrapper.appendChild(element);
    
    const overlay = document.createElement('div');
    overlay.className = 'cms-image-overlay';
    overlay.innerHTML = '<button class="cms-image-btn" onclick="uploadImage(\'' + contentKey + '\', \'' + field + '\')">🖼️ Ändra bild</button>';
    newWrapper.appendChild(overlay);
  }
}

/**
 * Image upload handler
 */
function initImageUpload() {
  // Create hidden file input
  if (!document.getElementById('cms-image-upload')) {
    const input = document.createElement('input');
    input.type = 'file';
    input.id = 'cms-image-upload';
    input.accept = 'image/*';
    input.style.display = 'none';
    document.body.appendChild(input);
    
    input.addEventListener('change', async function(e) {
      if (!e.target.files || !e.target.files[0]) return;
      
      const file = e.target.files[0];
      const contentKey = input.dataset.contentKey;
      const field = input.dataset.field;
      
      const formData = new FormData();
      formData.append('image', file);
      formData.append('key', contentKey);
      formData.append('field', field);
      
      try {
        const res = await fetch('/cms/api.php?action=upload', {
          method: 'POST',
          body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
          // Update image src
          const img = document.querySelector(`[data-content-key="${contentKey}"][data-field="${field}"]`);
          if (img) {
            img.src = data.url;
          }
          showNotification('Bild uppladdad!', 'success');
        } else {
          showNotification('Kunde inte ladda upp bild', 'error');
        }
      } catch (err) {
        console.error('Upload error:', err);
        showNotification('Kunde inte ladda upp bild', 'error');
      }
      
      // Reset input
      input.value = '';
    });
  }
}

/**
 * Trigger image upload
 */
function uploadImage(contentKey, field) {
  if (!window.CMS || !window.CMS.isEditMode) return;
  
  const input = document.getElementById('cms-image-upload');
  input.dataset.contentKey = contentKey;
  input.dataset.field = field;
  input.click();
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `cms-notification cms-notification--${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
