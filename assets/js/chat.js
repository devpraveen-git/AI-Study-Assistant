// AI Study Assistant v2 - Chat Tutor Engine
(function () {
  'use strict';

  const chatForm = document.getElementById('chatForm');
  const chatMessages = document.getElementById('chatMessages');
  const questionInput = document.getElementById('questionInput');
  const noteSelect = document.getElementById('noteSelect');
  const csrfToken = document.getElementById('csrfToken')?.value;
  const engineBadge = document.getElementById('engineBadge');

  if (!chatForm || !chatMessages || !questionInput) return;

  function scrollToBottom() {
    requestAnimationFrame(() => {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    });
    setTimeout(() => {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 60);
  }

  // Comprehensive Markdown to HTML formatter
  function formatMarkdown(rawText) {
    if (!rawText) return '';

    let text = String(rawText).replace(/\r\n/g, '\n').replace(/\r/g, '\n');

    // Extract and protect fenced code blocks first
    const codeBlocks = [];
    text = text.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, function (_, lang, code) {
      const idx = codeBlocks.length;
      const safeCode = code
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
      const langLabel = lang ? `<span class="badge bg-secondary mb-1">${lang}</span><br>` : '';
      codeBlocks.push(
        `<pre class="code-block my-2 p-3 rounded text-light" style="background:#091510;border:1px solid rgba(255,255,255,0.12);overflow-x:auto;">${langLabel}<code>${safeCode}</code></pre>`
      );
      return `__CODE_BLOCK_${idx}__`;
    });

    // Escape HTML outside code blocks
    text = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    // Inline code: `code`
    text = text.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded text-warning" style="background:rgba(245,158,11,0.12);font-size:0.9em;">$1</code>');

    // Headers
    text = text.replace(/^####\s+(.*$)/gim, '<h6 class="mt-3 mb-2 fw-bold text-info">$1</h6>');
    text = text.replace(/^###\s+(.*$)/gim, '<h5 class="mt-3 mb-2 fw-bold text-warning">$1</h5>');
    text = text.replace(/^##\s+(.*$)/gim, '<h4 class="mt-3 mb-2 fw-bold text-warning">$1</h4>');
    text = text.replace(/^#\s+(.*$)/gim, '<h3 class="mt-3 mb-2 fw-bold text-warning">$1</h3>');

    // Blockquotes: > text
    text = text.replace(/^>\s+(.*$)/gim, '<blockquote class="border-start border-warning border-3 ps-3 text-muted my-2">$1</blockquote>');

    // Horizontal rules: --- or ***
    text = text.replace(/^(\s*[-*_]\s*){3,}$/gim, '<hr class="my-3 border-secondary opacity-25">');

    // Bold & italic
    text = text.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Process lists and paragraphs line by line
    const lines = text.split('\n');
    const out = [];
    let inUl = false;
    let inOl = false;

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const ulMatch = line.match(/^\s*[-*]\s+(.*)$/);
      const olMatch = line.match(/^\s*(\d+)\.\s+(.*)$/);

      if (ulMatch) {
        if (inOl) { out.push('</ol>'); inOl = false; }
        if (!inUl) { out.push('<ul class="ps-3 my-2">'); inUl = true; }
        out.push(`<li>${ulMatch[1]}</li>`);
      } else if (olMatch) {
        if (inUl) { out.push('</ul>'); inUl = false; }
        if (!inOl) { out.push('<ol class="ps-3 my-2">'); inOl = true; }
        out.push(`<li>${olMatch[2]}</li>`);
      } else {
        if (inUl) { out.push('</ul>'); inUl = false; }
        if (inOl) { out.push('</ol>'); inOl = false; }

        const trimmed = line.trim();
        if (trimmed.startsWith('__CODE_BLOCK_') || trimmed.startsWith('<h') || trimmed.startsWith('<hr') || trimmed.startsWith('<blockquote')) {
          out.push(line);
        } else if (trimmed === '') {
          // empty spacer line
        } else {
          out.push(`<p class="mb-2">${line}</p>`);
        }
      }
    }
    if (inUl) out.push('</ul>');
    if (inOl) out.push('</ol>');

    let html = out.join('\n');

    // Restore protected code blocks
    codeBlocks.forEach((cb, idx) => {
      html = html.replace(`__CODE_BLOCK_${idx}__`, cb);
    });

    return html;
  }

  function appendMessage(text, role, isHtml = false) {
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${role}`;
    if (isHtml) {
      bubble.innerHTML = text;
    } else {
      bubble.textContent = text;
    }
    chatMessages.appendChild(bubble);
    scrollToBottom();
    return bubble;
  }

  function appendTypingIndicator() {
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble assistant typing-indicator';
    bubble.innerHTML = '<span class="spinner-grow spinner-grow-sm text-warning me-2"></span>AI is reading your notes...';
    chatMessages.appendChild(bubble);
    scrollToBottom();
    return bubble;
  }

  // Format all initial assistant messages loaded from PHP with data-raw
  document.querySelectorAll('.chat-bubble.assistant[data-raw]').forEach((el) => {
    const raw = el.getAttribute('data-raw');
    if (raw) {
      el.innerHTML = formatMarkdown(raw);
    }
  });
  scrollToBottom();

  // Handle Suggestion Chips
  document.querySelectorAll('.chip-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const query = btn.dataset.query;
      if (query) {
        questionInput.value = query;
        chatForm.dispatchEvent(new Event('submit'));
      }
    });
  });

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const query = questionInput.value.trim();
    if (!query) return;

    const noteId = noteSelect ? noteSelect.value : 0;
    if (!noteId) {
      alert('Please upload or select a note first.');
      return;
    }

    appendMessage(query, 'user');
    questionInput.value = '';
    const typing = appendTypingIndicator();

    try {
      const response = await fetch('../api/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          note_id: noteId,
          question: query,
          csrf_token: csrfToken
        })
      });

      const data = await response.json();
      typing.remove();

      if (data.ok) {
        appendMessage(formatMarkdown(data.answer), 'assistant', true);
        if (engineBadge) {
          if (data.engine === 'gemini') {
            engineBadge.className = 'badge text-bg-success rounded-pill px-3 py-2 d-none d-md-inline-block';
            engineBadge.innerHTML = '<i class="fa-solid fa-cloud me-1"></i>Gemini AI Ready';
          } else {
            engineBadge.className = 'badge text-bg-warning text-dark rounded-pill px-3 py-2 d-none d-md-inline-block';
            engineBadge.innerHTML = '<i class="fa-solid fa-brain me-1"></i>Note Intelligence Ready';
          }
        }
      } else {
        appendMessage(`<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>${data.message || 'Error answering your question.'}</span>`, 'assistant', true);
      }
    } catch (err) {
      typing.remove();
      appendMessage('<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Network error. Could not connect to AI service.</span>', 'assistant', true);
    }
  });
})();
