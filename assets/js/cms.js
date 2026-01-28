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
    originalValue = element.textContent.trim();
    
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
    
    inputElement.value = element.textContent.trim();
    inputElement.className = 'bg-white text-woodsmoke rounded-b-lg p-4 w-full resize-none font-inherit text-inherit';
    inputElement.style.border = '2px solid #e5e7eb';
    inputElement.style.borderTop = 'none';
    inputElement.style.outline = 'none';
    inputElement.style.boxShadow = 'none';
    inputElement.style.transition = 'border-color 0.2s';
    inputElement.style.fontSize = 'inherit';
    inputElement.style.lineHeight = 'inherit';
    inputElement.style.fontWeight = 'inherit';
    inputElement.style.width = '100%';
    inputElement.style.boxSizing = 'border-box';
    
    // Focus state
    inputElement.addEventListener('focus', function() {
      inputElement.style.borderColor = '#ff5722';
    });
    inputElement.addEventListener('blur', function() {
      inputElement.style.borderColor = '#e5e7eb';
    });
    
    if (isMultiline) {
      // Auto-resize textarea based on content
      inputElement.rows = 1;
      inputElement.style.minHeight = '80px';
      inputElement.style.overflow = 'hidden';
      
      // Function to auto-resize
      const autoResize = () => {
        inputElement.style.height = 'auto';
        inputElement.style.height = inputElement.scrollHeight + 'px';
      };
      
      // Initial resize
      setTimeout(autoResize, 0);
      
      // Resize on input
      inputElement.addEventListener('input', autoResize);
    } else {
      inputElement.style.height = '48px';
    }
    
    // Create buttons container (above input)
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'flex gap-2 items-center justify-end bg-white rounded-t-lg p-2 z-50';
    buttonsDiv.style.borderBottom = '1px solid #e5e7eb';
    buttonsDiv.style.marginBottom = '0';
    buttonsDiv.style.boxShadow = 'none';
    
    // Save button
    const saveBtn = document.createElement('button');
    saveBtn.textContent = '✓ Spara';
    saveBtn.className = 'px-4 py-2 bg-persimmon text-white text-xs font-semibold rounded-md hover:bg-persimmon/90 disabled:opacity-50 transition';
    saveBtn.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
    saveBtn.onclick = async function() {
      saveBtn.disabled = true;
      saveBtn.textContent = 'Sparar...';
      await handleSave(inputElement.value);
    };
    
    // Cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = '✕';
    cancelBtn.className = 'px-3 py-2 text-gray-700 font-semibold rounded-md hover:bg-gray-100 transition';
    cancelBtn.style.fontSize = '18px';
    cancelBtn.style.lineHeight = '1';
    cancelBtn.style.backgroundColor = 'transparent';
    cancelBtn.title = 'Avbryt';
    cancelBtn.onclick = function() {
      handleCancel();
    };
    
    buttonsDiv.appendChild(cancelBtn);
    buttonsDiv.appendChild(saveBtn);
    
    wrapper.appendChild(buttonsDiv);
    wrapper.appendChild(inputElement);
    
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
        // Trim whitespace to avoid spacing issues
        const trimmedValue = newValue.trim();
        
        // Fetch current content
        const res = await fetch(`/cms/api.php?action=get&_t=${Date.now()}`);
        const currentData = await res.json();
        
        // Update section
        const updatedSection = {
          ...(currentData[contentKey] || {}),
          [field]: trimmedValue
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
          // Update the text node directly without changing DOM structure
          const textNode = Array.from(element.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
          if (textNode) {
            textNode.nodeValue = trimmedValue;
          } else {
            element.textContent = trimmedValue;
          }
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
      // Update the text node directly without changing DOM structure
      const textNode = Array.from(element.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
      if (textNode) {
        textNode.nodeValue = originalValue;
      } else {
        element.textContent = originalValue;
      }
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
