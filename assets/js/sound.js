class SoundSynth {
  constructor() {
    this.ctx = null;
    this.lobbyInterval = null;
    this.isMuted = false;
    this.kahootPlaying = false;
    this.kahootTimeout = null;
    
    // Check localStorage settings
    const musicSetting = localStorage.getItem('settings_music_enabled');
    this.isMuted = (musicSetting === 'false') ? true : false;

    this.reloadTracks();
    
    this.currentQuestionIndex = null;
    this.isPlayingStartSequence = false;
  }

  reloadTracks() {
    const startSrc = localStorage.getItem('settings_start_music') || 'assets/audio/chalo.mp3';
    const questionSrc = localStorage.getItem('settings_question_music') || 'SYNTH_KAHOOT_QUESTION';
    const lockedSrc = localStorage.getItem('settings_locked_music') || 'SYNTH_KAHOOT_LOCKED';
    const wrongSrc = localStorage.getItem('settings_wrong_music') || 'SYNTH_KAHOOT_WRONG';

    this.startSrc = startSrc;
    this.questionSrc = questionSrc;
    this.lockedSrc = lockedSrc;
    this.wrongSrc = wrongSrc;

    // Load chalo or other start file if not synth
    if (!startSrc.startsWith('SYNTH_')) {
      this.chalo = new Audio(startSrc);
      this.chalo.loop = false;
      this.chalo.muted = this.isMuted;
      this.chalo.onended = () => {
        if (this.isPlayingStartSequence && !this.isMuted) {
          this.isPlayingStartSequence = false;
          this.playQuestionMusic();
        }
      };
    } else {
      this.chalo = null;
    }

    // Load file-based tracks only if not synth-based
    if (!questionSrc.startsWith('SYNTH_')) {
      this.customQuestion = new Audio(questionSrc);
      this.customQuestion.loop = false;
      this.customQuestion.muted = this.isMuted;
    } else {
      this.customQuestion = null;
    }

    if (!lockedSrc.startsWith('SYNTH_')) {
      this.customLocked = new Audio(lockedSrc);
      this.customLocked.loop = false;
      this.customLocked.muted = this.isMuted;
    } else {
      this.customLocked = null;
    }

    if (!wrongSrc.startsWith('SYNTH_')) {
      this.customWrong = new Audio(wrongSrc);
      this.customWrong.loop = false;
      this.customWrong.muted = this.isMuted;
    } else {
      this.customWrong = null;
    }
  }

  initCtx() {
    if (!this.ctx) {
      this.ctx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (this.ctx.state === 'suspended') {
      this.ctx.resume();
    }
  }

  setMute(mute) {
    this.isMuted = mute;
    if (this.chalo) this.chalo.muted = mute;
    if (this.customQuestion) this.customQuestion.muted = mute;
    if (this.customLocked) this.customLocked.muted = mute;
    if (this.customWrong) this.customWrong.muted = mute;
    
    if (mute) {
      this.stopAll(true);
    }
  }

  getMute() {
    return this.isMuted;
  }

  playLobby() {
    this.stopAll(true);
    if (this.isMuted) return;
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;
    
    let step = 0;
    // Bouncy Kahoot-style lobby chord progression (C - G - Am - F) arpeggios
    const chords = [
      [261.63, 329.63, 392.00, 523.25], // C Major
      [293.66, 349.23, 392.00, 587.33], // G Major
      [220.00, 261.63, 329.63, 440.00], // A Minor
      [261.63, 349.23, 440.00, 523.25]  // F Major
    ];
    
    const playNote = () => {
      if (this.isMuted || !this.ctx) return;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      
      const chordIndex = Math.floor(step / 4) % chords.length;
      const noteIndex = step % 4;
      const freq = chords[chordIndex][noteIndex];
      
      osc.type = 'sine';
      osc.frequency.setValueAtTime(freq, ctx.currentTime);
      
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
      
      osc.connect(gain);
      gain.connect(ctx.destination);
      
      osc.start();
      osc.stop(ctx.currentTime + 0.4);
      
      step++;
    };

    playNote();
    this.lobbyInterval = setInterval(playNote, 250);
  }

  playQuestionMusic() {
    if (this.isMuted) return;
    if (this.questionSrc === 'SYNTH_KAHOOT_QUESTION') {
      this.startKahootQuestionMusic();
    } else if (this.customQuestion) {
      this.customQuestion.currentTime = 0;
      this.customQuestion.play().catch(e => console.log('Audio playback prevented', e));
    }
  }

  startKahootQuestionMusic() {
    this.stopAll();
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;

    this.kahootPlaying = true;
    
    const bpm = 125;
    const stepDuration = 60 / bpm / 2; // 8th note duration (~240ms)
    let nextNoteTime = ctx.currentTime;
    let step = 0;

    // Classic 16-step pentatonic melody that feels exactly like a game show/Kahoot!
    const melody = [
      329.63, 0, 392.00, 329.63, 0, 293.66, 440.00, 329.63,
      329.63, 0, 392.00, 440.00, 523.25, 440.00, 392.00, 329.63
    ];
    
    const bassline = [
      110.00, 110.00, 130.81, 110.00, 98.00, 98.00, 110.00, 110.00,
      110.00, 110.00, 130.81, 110.00, 146.83, 146.83, 130.81, 98.00
    ];

    const scheduler = () => {
      if (!this.kahootPlaying || this.isMuted) return;
      
      while (nextNoteTime < ctx.currentTime + 0.1) {
        this.scheduleKahootNote(step, nextNoteTime, melody[step % 16], bassline[step % 16]);
        nextNoteTime += stepDuration;
        step++;
      }
      
      this.kahootTimeout = setTimeout(scheduler, 25);
    };

    scheduler();
  }

  scheduleKahootNote(step, time, melFreq, bassFreq) {
    const ctx = this.ctx;
    
    // 1. Play Bass note (always ticking)
    if (bassFreq > 0) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(bassFreq, time);
      
      gain.gain.setValueAtTime(0.12, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.2);
      
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.25);
    }
    
    // 2. Play Melody note
    if (melFreq > 0 && (step % 2 === 0 || step % 3 === 0)) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      
      osc.type = 'sine';
      osc.frequency.setValueAtTime(melFreq, time);
      
      gain.gain.setValueAtTime(0.07, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.18);
      
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.2);
    }

    // 3. Rhythmic high-hat sound for extra groove!
    if (step % 4 === 2) {
      const noise = ctx.createBufferSource();
      const noiseFilter = ctx.createBiquadFilter();
      const noiseGain = ctx.createGain();
      
      const bufferSize = ctx.sampleRate * 0.04;
      const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
      const data = buffer.getChannelData(0);
      for (let i = 0; i < bufferSize; i++) {
        data[i] = Math.random() * 2 - 1;
      }
      
      noise.buffer = buffer;
      
      noiseFilter.type = 'highpass';
      noiseFilter.frequency.setValueAtTime(7000, time);
      
      noiseGain.gain.setValueAtTime(0.015, time);
      noiseGain.gain.exponentialRampToValueAtTime(0.001, time + 0.04);
      
      noise.connect(noiseFilter);
      noiseFilter.connect(noiseGain);
      noiseGain.connect(ctx.destination);
      noise.start(time);
      noise.stop(time + 0.05);
    }
  }

  playLocked() {
    if (this.isMuted) return;
    if (this.lockedSrc === 'SYNTH_KAHOOT_LOCKED') {
      this.playKahootLocked();
    } else if (this.customLocked) {
      this.customLocked.currentTime = 0;
      this.customLocked.play().catch(e => console.log('Audio playback prevented', e));
    }
  }

  playKahootLocked() {
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;
    
    const time = ctx.currentTime;
    
    // Quick rising pitch sine wave
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    
    osc.type = 'sine';
    osc.frequency.setValueAtTime(523.25, time); // C5
    osc.frequency.exponentialRampToValueAtTime(1046.50, time + 0.15); // C6
    
    gain.gain.setValueAtTime(0.2, time);
    gain.gain.exponentialRampToValueAtTime(0.001, time + 0.25);
    
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start(time);
    osc.stop(time + 0.3);
  }

  playWrong() {
    if (this.isMuted) return;
    if (this.wrongSrc === 'SYNTH_KAHOOT_WRONG') {
      this.playKahootWrong();
    } else if (this.customWrong) {
      this.customWrong.currentTime = 0;
      this.customWrong.play().catch(e => console.log('Audio playback prevented', e));
    }
  }

  playKahootWrong() {
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;
    
    const time = ctx.currentTime;
    
    // Slide down retro frequency
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(180, time);
    osc.frequency.linearRampToValueAtTime(60, time + 0.6);
    
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(400, time);
    
    gain.gain.setValueAtTime(0.25, time);
    gain.gain.exponentialRampToValueAtTime(0.001, time + 0.7);
    
    osc.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);
    osc.start(time);
    osc.stop(time + 0.85);
  }

  playStartSequence() {
    this.stopAll(true);
    if (this.isMuted) return;
    this.isPlayingStartSequence = true;
    if (this.chalo) {
      this.chalo.currentTime = 0;
      this.chalo.play().catch(e => console.log('Audio playback prevented', e));
    } else {
      this.playQuestionMusic();
    }
  }

  playCountdown(timeLeft, questionIndex = 1) {
    this.currentTimeLeft = timeLeft;
    if (this.isMuted) {
      this.stopAll(true);
      return;
    }
    
    this.initCtx();
    const ctx = this.ctx;

    if (timeLeft > 0) {
      if (!this.isPlayingStartSequence) {
        if (this.questionSrc === 'SYNTH_KAHOOT_QUESTION') {
          if (!this.kahootPlaying) {
            this.startKahootQuestionMusic();
          }
        } else if (this.customQuestion) {
          if (this.customQuestion.paused) {
            this.customQuestion.currentTime = 0;
            this.customQuestion.play().catch(e => console.log('Audio playback prevented', e));
          }
        }
      }
    } else if (timeLeft === 0) {
      this.stopAll(true);
      
      if (!this.isMuted && ctx) {
        const oscHigh = ctx.createOscillator();
        const gainHigh = ctx.createGain();
        oscHigh.type = 'sawtooth';
        oscHigh.frequency.setValueAtTime(150, ctx.currentTime);
        oscHigh.frequency.linearRampToValueAtTime(50, ctx.currentTime + 1.0);
        gainHigh.gain.setValueAtTime(0.7, ctx.currentTime);
        gainHigh.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.0);
        
        oscHigh.connect(gainHigh);
        gainHigh.connect(ctx.destination);
        oscHigh.start();
        oscHigh.stop(ctx.currentTime + 1.5);
      }
    }
  }

  playVictory() {
    this.stopAll(true);
    if (this.isMuted) return;
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;

    const notes = [261.63, 329.63, 392.00, 523.25, 659.25, 783.99, 1046.50];
    notes.forEach((freq, idx) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.08);
      
      gain.gain.setValueAtTime(0.0, ctx.currentTime + idx * 0.08);
      gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + idx * 0.08 + 0.04);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.08 + 1.0);
      
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(ctx.currentTime + idx * 0.08);
      osc.stop(ctx.currentTime + idx * 0.08 + 1.2);
    });
  }

  stopAll(force = false) {
    if (this.lobbyInterval) {
      clearInterval(this.lobbyInterval);
      this.lobbyInterval = null;
    }
    
    this.kahootPlaying = false;
    if (this.kahootTimeout) {
      clearTimeout(this.kahootTimeout);
      this.kahootTimeout = null;
    }

    if (force) {
      this.isPlayingStartSequence = false;
      if (this.chalo && !this.chalo.paused) {
        this.chalo.pause();
        this.chalo.currentTime = 0;
      }
      if (this.customQuestion && !this.customQuestion.paused) {
        this.customQuestion.pause();
        this.customQuestion.currentTime = 0;
      }
    }
    
    if (!this.isPlayingStartSequence) {
      if (this.customLocked && !this.customLocked.paused) {
        this.customLocked.pause();
        this.customLocked.currentTime = 0;
      }
      if (this.customWrong && !this.customWrong.paused) {
        this.customWrong.pause();
        this.customWrong.currentTime = 0;
      }
    }
    this.currentQuestionIndex = null;
  }

  stopKBCMusic() {
    this.stopAll(true);
  }
}

window.sound = new SoundSynth();
