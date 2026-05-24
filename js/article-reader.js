(function () {
    const reader = document.querySelector('[data-reader]');
    const content = document.getElementById('post-content-wrapper');
    if (!reader || !content) return;

    const audio = document.getElementById('post-audio-player');
    const playButton = reader.querySelector('[data-reader-play]');
    const pauseButton = reader.querySelector('[data-reader-pause]');
    const rateSelect = reader.querySelector('[data-reader-rate]');
    const status = reader.querySelector('[data-reader-status]');
    const progress = reader.querySelector('[data-reader-progress]');
    const pageNumber = Number(reader.dataset.page || 1);
    const nextUrl = reader.dataset.nextUrl || '';
    let pageData = null;
    let wordSpans = [];
    let wordGroups = [];
    let wordStarts = [];
    let utterance = null;
    let isSpeechMode = false;
    let activeCueIndex = -1;

    function setStatus(text) {
        if (status) status.textContent = text;
    }

    function wordsFromText(text) {
        const words = [];
        const starts = [];
        const re = /\S+/g;
        let match;
        while ((match = re.exec(text || ''))) {
            words.push(match[0]);
            starts.push(match.index);
        }
        wordStarts = starts;
        return words;
    }

    function wrapContentWords() {
        wordSpans = [];
        wordGroups = [];
        let currentWordOpen = false;
        const walker = document.createTreeWalker(content, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return (node.nodeValue || '') !== '' ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);

        nodes.forEach(node => {
            const text = node.nodeValue || '';
            const fragment = document.createDocumentFragment();
            const parts = text.match(/\s+|\S+/g) || [];
            parts.forEach(part => {
                if (/^\s+$/.test(part)) {
                    fragment.appendChild(document.createTextNode(part));
                    currentWordOpen = false;
                    return;
                }
                if (!currentWordOpen || wordGroups.length === 0) {
                    wordGroups.push([]);
                }
                const span = document.createElement('span');
                span.className = 'reader-word';
                span.dataset.wordIndex = String(wordGroups.length - 1);
                span.textContent = part;
                wordGroups[wordGroups.length - 1].push(span);
                wordSpans.push(span);
                fragment.appendChild(span);
                currentWordOpen = true;
            });
            node.parentNode.replaceChild(fragment, node);
        });
    }

    function clearHighlight() {
        wordSpans.forEach(span => span.classList.remove('is-current'));
    }

    function updateProgress(index) {
        if (progress) {
            progress.style.width = `${Math.round(((index + 1) / Math.max(1, wordGroups.length || wordSpans.length)) * 100)}%`;
        }
    }

    function highlightWord(index) {
        const groups = wordGroups.length ? wordGroups : wordSpans.map(span => [span]);
        if (!groups.length) return;
        const safeIndex = Math.max(0, Math.min(groups.length - 1, index));
        clearHighlight();
        const group = groups[safeIndex];
        group.forEach(span => span.classList.add('is-current'));
        group[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        updateProgress(safeIndex);
    }

    function highlightCue(cue) {
        const groups = wordGroups.length ? wordGroups : wordSpans.map(span => [span]);
        if (!groups.length || !cue) return;
        const start = Math.max(0, Math.min(groups.length - 1, Number(cue.start_word || 0)));
        const end = Math.max(start, Math.min(groups.length - 1, Number(cue.end_word ?? start)));
        clearHighlight();
        for (let i = start; i <= end; i++) {
            groups[i].forEach(span => span.classList.add('is-current'));
        }
        groups[start][0].scrollIntoView({ block: 'center', behavior: 'smooth' });
        updateProgress(end);
    }

    function cueForTime(time) {
        const cues = Array.isArray(pageData?.cues) ? pageData.cues : [];
        if (!cues.length) return null;

        let low = 0;
        let high = cues.length - 1;
        while (low <= high) {
            const mid = Math.floor((low + high) / 2);
            const cue = cues[mid];
            if (time < Number(cue.start || 0)) {
                high = mid - 1;
            } else if (time > Number(cue.end || 0)) {
                low = mid + 1;
            } else {
                activeCueIndex = mid;
                return cue;
            }
        }

        const index = Math.max(0, Math.min(cues.length - 1, high));
        activeCueIndex = index;
        return cues[index];
    }

    function highlightAudioTime() {
        if (!audio || !pageData) return;
        const duration = audio.duration || 0;
        const estimated = Number(pageData.estimated_seconds || 0);
        const cueTime = duration > 0 && estimated > 0
            ? audio.currentTime * (estimated / duration)
            : audio.currentTime;
        const cue = cueForTime(cueTime);
        if (cue) {
            highlightCue(cue);
            return;
        }

        const safeDuration = duration || estimated || 1;
        const ratio = Math.max(0, Math.min(1, audio.currentTime / safeDuration));
        highlightWord(Math.floor(ratio * Math.max(1, wordGroups.length || wordSpans.length)));
    }

    function wordIndexFromChar(charIndex) {
        let index = 0;
        for (let i = 0; i < wordStarts.length; i++) {
            if (wordStarts[i] <= charIndex) index = i;
            else break;
        }
        return index;
    }

    function goNextPage() {
        clearHighlight();
        if (nextUrl) {
            window.location.href = nextUrl;
        } else {
            setStatus('Finished.');
        }
    }

    function playSpeech() {
        if (!('speechSynthesis' in window) || !('SpeechSynthesisUtterance' in window)) {
            setStatus('No saved audio file is available and this browser does not support speech playback.');
            return;
        }

        if (window.speechSynthesis.paused && utterance) {
            window.speechSynthesis.resume();
            setStatus('Reading with browser voice.');
            return;
        }

        window.speechSynthesis.cancel();
        utterance = new SpeechSynthesisUtterance(pageData.text || content.textContent || '');
        utterance.rate = Number(rateSelect?.value || 1);
        utterance.onstart = () => setStatus('Reading with browser voice.');
        utterance.onboundary = event => {
            if (typeof event.charIndex === 'number') {
                highlightWord(wordIndexFromChar(event.charIndex));
            }
        };
        utterance.onend = goNextPage;
        utterance.onerror = () => setStatus('Speech playback stopped.');
        window.speechSynthesis.speak(utterance);
        isSpeechMode = true;
    }

    function playAudio() {
        if (!audio || !pageData.audio_url) {
            playSpeech();
            return;
        }

        isSpeechMode = false;
        audio.playbackRate = Number(rateSelect?.value || 1);
        audio.play().then(() => {
            setStatus('Playing generated audio.');
        }).catch(() => {
            playSpeech();
        });
    }

    function pauseReader() {
        if (isSpeechMode && 'speechSynthesis' in window) {
            window.speechSynthesis.pause();
            setStatus('Paused.');
            return;
        }
        if (audio) {
            audio.pause();
            setStatus('Paused.');
        }
    }

    function configureAudio() {
        if (!audio || !pageData?.audio_url) {
            setStatus('Saved audio is not available yet. Browser voice will be used.');
            return;
        }
        audio.src = pageData.audio_url;
        audio.addEventListener('timeupdate', highlightAudioTime);
        audio.addEventListener('seeked', highlightAudioTime);
        audio.addEventListener('ended', goNextPage);
        setStatus('Generated audio is ready.');
    }

    function loadManifest() {
        return fetch(reader.dataset.manifest, { cache: 'no-store' })
            .then(response => {
                if (!response.ok) throw new Error('Reader transcript is not generated yet.');
                return response.json();
            })
            .then(manifest => {
                pageData = (manifest.pages || []).find(page => Number(page.page) === pageNumber) || null;
                if (!pageData) throw new Error('Reader transcript is missing this page.');
                wordsFromText(pageData.text || content.textContent || '');
                wrapContentWords();
                activeCueIndex = -1;
                configureAudio();
                if (reader.dataset.autoplay === '1') {
                    playAudio();
                }
            })
            .catch(error => setStatus(error.message));
    }

    playButton?.addEventListener('click', playAudio);
    pauseButton?.addEventListener('click', pauseReader);
    rateSelect?.addEventListener('change', () => {
        if (audio) audio.playbackRate = Number(rateSelect.value || 1);
        if (isSpeechMode && 'speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            playSpeech();
        }
    });

    loadManifest();
})();
