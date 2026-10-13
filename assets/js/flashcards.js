// AI Study Assistant v2 - Flashcard Study Engine
(function () {
  'use strict';

  const noteSelect = document.getElementById('flashcardNoteSelect');
  const cardInner = document.getElementById('flashcardInner');
  const cardFront = document.getElementById('cardFrontText');
  const cardBack = document.getElementById('cardBackText');
  const cardCategory = document.getElementById('cardCategoryBadge');
  const prevBtn = document.getElementById('prevCardBtn');
  const nextBtn = document.getElementById('nextCardBtn');
  const flipBtn = document.getElementById('flipCardBtn');
  const knowBtn = document.getElementById('knowCardBtn');
  const dontKnowBtn = document.getElementById('dontKnowCardBtn');
  const cardCounter = document.getElementById('cardCounter');
  const progressBar = document.getElementById('flashcardProgressBar');
  const emptyState = document.getElementById('flashcardEmptyState');
  const cardScene = document.getElementById('flashcardSceneWrapper');
  const csrfToken = document.getElementById('csrfToken')?.value;

  if (!cardInner || !noteSelect) return;

  let cards = [];
  let currentIndex = 0;

  async function loadCards() {
    const noteId = noteSelect.value;
    if (!noteId) return;

    try {
      const res = await fetch(`../api/flashcards.php?note_id=${noteId}`);
      const data = await res.json();

      if (data.ok && data.flashcards?.length > 0) {
        cards = data.flashcards;
        currentIndex = 0;
        cardScene.classList.remove('d-none');
        emptyState.classList.add('d-none');
        renderCard();
      } else {
        cards = [];
        cardScene.classList.add('d-none');
        emptyState.classList.remove('d-none');
      }
    } catch (e) {
      console.error('Error loading flashcards', e);
    }
  }

  function renderCard() {
    if (cards.length === 0) return;
    const card = cards[currentIndex];

    cardInner.classList.remove('is-flipped');
    cardFront.textContent = card.front;
    cardBack.textContent = card.back;
    if (cardCategory) cardCategory.textContent = card.category || 'General';

    if (cardCounter) cardCounter.textContent = `Card ${currentIndex + 1} of ${cards.length}`;
    if (progressBar) {
      const pct = Math.round(((currentIndex + 1) / cards.length) * 100);
      progressBar.style.width = `${pct}%`;
    }

    if (prevBtn) prevBtn.disabled = currentIndex === 0;
    if (nextBtn) nextBtn.disabled = currentIndex === cards.length - 1;
  }

  function flipCard() {
    cardInner.classList.toggle('is-flipped');
  }

  cardInner.addEventListener('click', flipCard);
  if (flipBtn) flipBtn.addEventListener('click', flipCard);

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (currentIndex > 0) {
        currentIndex--;
        renderCard();
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (currentIndex < cards.length - 1) {
        currentIndex++;
        renderCard();
      }
    });
  }

  async function markMastery(level) {
    if (cards.length === 0) return;
    const card = cards[currentIndex];

    try {
      await fetch('../api/flashcards.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          card_id: card.id,
          mastery: level,
          csrf_token: csrfToken
        })
      });
    } catch (e) {
      // Ignore
    }

    if (currentIndex < cards.length - 1) {
      currentIndex++;
      renderCard();
    } else {
      alert('Congratulations! You have completed all flashcards for this note.');
    }
  }

  if (knowBtn) knowBtn.addEventListener('click', () => markMastery(2));
  if (dontKnowBtn) dontKnowBtn.addEventListener('click', () => markMastery(0));

  noteSelect.addEventListener('change', loadCards);

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (e.code === 'Space' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
      e.preventDefault();
      flipCard();
    } else if (e.code === 'ArrowRight') {
      if (currentIndex < cards.length - 1) {
        currentIndex++;
        renderCard();
      }
    } else if (e.code === 'ArrowLeft') {
      if (currentIndex > 0) {
        currentIndex--;
        renderCard();
      }
    }
  });

  // Initial load
  loadCards();
})();
