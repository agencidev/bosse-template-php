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

        // Fetch current content (includes CSRF token)
        const res = await fetch(`/cms/api.php?action=get&_t=${Date.now()}`);
        const currentData = await res.json();
        const csrfToken = currentData._csrf || '';

        // Update section
        const updatedSection = {
          ...(currentData[contentKey] || {}),
          [field]: trimmedValue
        };

        // Save to server with CSRF token
        const saveRes = await fetch('/cms/api.php?action=update', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
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
      
      // Get CSRF token first
      const tokenRes = await fetch(`/cms/api.php?action=get&_t=${Date.now()}`);
      const tokenData = await tokenRes.json();

      const formData = new FormData();
      formData.append('image', file);
      formData.append('key', contentKey);
      formData.append('field', field);
      formData.append('csrf_token', tokenData._csrf || '');

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
 * Show notification (toast)
 * @param {string} message - Message to display
 * @param {string} type - 'success', 'error', or 'info'
 * @param {object} options - Optional settings: { duration: 5000, details: '' }
 */
function showNotification(message, type = 'info', options = {}) {
  const duration = options.duration || 5000;
  const details = options.details || '';

  // Inject CSS once
  if (!document.getElementById('cms-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'cms-notification-styles';
    style.textContent = `
      .cms-toast-container {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        z-index: 10001;
        pointer-events: none;
      }
      .cms-toast {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        animation: cmsToastIn 0.3s ease-out;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15), 0 4px 6px rgba(0,0,0,0.1);
        max-width: 360px;
        pointer-events: auto;
      }
      .cms-toast--success {
        background: #10b981;
        color: white;
      }
      .cms-toast--error {
        background: #ef4444;
        color: white;
      }
      .cms-toast--info {
        background: #3b82f6;
        color: white;
      }
      .cms-toast-icon {
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
      }
      .cms-toast-content {
        flex: 1;
      }
      .cms-toast-message {
        font-weight: 600;
      }
      .cms-toast-details {
        font-size: 0.8125rem;
        opacity: 0.9;
        margin-top: 0.25rem;
      }
      .cms-toast-close {
        flex-shrink: 0;
        background: none;
        border: none;
        color: inherit;
        opacity: 0.7;
        cursor: pointer;
        padding: 0;
        font-size: 1.25rem;
        line-height: 1;
      }
      .cms-toast-close:hover {
        opacity: 1;
      }
      .cms-toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: rgba(255,255,255,0.3);
        border-radius: 0 0 0.75rem 0.75rem;
        overflow: hidden;
      }
      .cms-toast-progress-bar {
        height: 100%;
        background: rgba(255,255,255,0.7);
        animation: cmsToastProgress linear forwards;
      }
      @keyframes cmsToastIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes cmsToastOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
      @keyframes cmsToastProgress {
        from { width: 100%; }
        to { width: 0%; }
      }
      .cms-editable-active {
        outline: 2px dashed rgba(254, 79, 42, 0.4);
        outline-offset: 4px;
        cursor: pointer;
        transition: outline-color 0.2s;
      }
      .cms-editable-active:hover {
        outline-color: rgba(254, 79, 42, 0.8);
      }
      .cms-image-wrapper {
        position: relative;
        display: inline-block;
      }
      .cms-image-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: inherit;
      }
      .cms-edit-mode .cms-image-wrapper:hover .cms-image-overlay {
        display: flex;
      }
      .cms-image-btn {
        padding: 0.5rem 1rem;
        background: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
      }
    `;
    document.head.appendChild(style);
  }

  // Get or create container
  let container = document.getElementById('cms-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'cms-toast-container';
    container.className = 'cms-toast-container';
    document.body.appendChild(container);
  }

  // Icon based on type
  const icons = {
    success: '<svg class="cms-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    error: '<svg class="cms-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
    info: '<svg class="cms-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
  };

  const toast = document.createElement('div');
  toast.className = `cms-toast cms-toast--${type}`;
  toast.style.position = 'relative';
  toast.innerHTML = `
    ${icons[type] || icons.info}
    <div class="cms-toast-content">
      <div class="cms-toast-message">${escapeHtml(message)}</div>
      ${details ? `<div class="cms-toast-details">${escapeHtml(details)}</div>` : ''}
    </div>
    <button class="cms-toast-close" aria-label="Stäng">&times;</button>
    <div class="cms-toast-progress">
      <div class="cms-toast-progress-bar" style="animation-duration: ${duration}ms"></div>
    </div>
  `;

  // Close button
  toast.querySelector('.cms-toast-close').addEventListener('click', () => {
    dismissToast(toast);
  });

  container.appendChild(toast);

  // Auto-dismiss
  const timeoutId = setTimeout(() => {
    dismissToast(toast);
  }, duration);

  // Pause on hover
  toast.addEventListener('mouseenter', () => {
    const progressBar = toast.querySelector('.cms-toast-progress-bar');
    if (progressBar) progressBar.style.animationPlayState = 'paused';
  });

  toast.addEventListener('mouseleave', () => {
    const progressBar = toast.querySelector('.cms-toast-progress-bar');
    if (progressBar) progressBar.style.animationPlayState = 'running';
  });

  function dismissToast(el) {
    clearTimeout(timeoutId);
    el.style.animation = 'cmsToastOut 0.3s ease-out forwards';
    setTimeout(() => el.remove(), 300);
  }
}

/**
 * Escape HTML for safe insertion
 */
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1024 / 1024).toFixed(1) + ' MB';
}
