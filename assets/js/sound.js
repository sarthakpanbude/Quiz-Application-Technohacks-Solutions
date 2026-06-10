class SoundSynth {
  constructor() {
    this.ctx = null;
    this.audioConfig = null;
    this.playingTracks = {};
    this.lobbyInterval = null;
    this.kahootPlaying = false;
    this.kahootTimeout = null;
    this.isMuted = false;
    this.audioCache = {};

    // Initial load of global audio settings
    fetch('api.php?action=get_quiz_audio_settings&quiz_id=0')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          this.setAudioConfig(data.audio_config);
        }
      }).catch(e => console.log('Error initializing sound config:', e));
  }

  setAudioConfig(config) {
    if (!config) return;
    this.audioConfig = config;
    this.isMuted = !!config.global.mute_all;

    // Update active loop states or volumes of currently playing tracks
    for (const key in this.playingTracks) {
      const track = this.playingTracks[key];
      const catConfig = this.audioConfig.categories[key];
      if (track && catConfig) {
        if (track instanceof Audio) {
          if (!catConfig.enabled || this.isMuted) {
            track.pause();
            track.currentTime = 0;
            delete this.playingTracks[key];
          } else {
            track.volume = this.resolveVolume(key);
            track.loop = !!catConfig.loop;
          }
        }
      }
    }
  }

  resolveVolume(key) {
    if (!this.audioConfig) return 0.5;
    const catConfig = this.audioConfig.categories[key];
    if (!catConfig || !catConfig.enabled || this.isMuted) return 0;
    
    const master = this.audioConfig.global.master_volume ?? 1.0;
    const music = this.audioConfig.global.music_volume ?? 1.0;
    const effects = this.audioConfig.global.effects_volume ?? 1.0;
    
    const type = this.getCategoryType(key);
    const multiplier = (type === 'music') ? music : effects;
    
    return master * multiplier * (catConfig.volume ?? 0.8);
  }

  getCategoryType(key) {
    const musicKeys = ['lobby', 'start', 'background', 'countdown', 'leaderboard', 'winner', 'top3', 'completion', 'q_countdown'];
    return musicKeys.includes(key) ? 'music' : 'effect';
  }

  playCategory(key) {
    if (!this.audioConfig) return;

    const catConfig = this.audioConfig.categories[key];
    if (!catConfig || !catConfig.enabled || this.isMuted) return;

    const path = catConfig.file_path;
    const volume = this.resolveVolume(key);
    if (volume <= 0) return;

    if (path.startsWith('SYNTH_')) {
      this.playSynthSound(key, path, volume, !!catConfig.loop);
    } else {
      this.playFileSound(key, path, volume, !!catConfig.loop);
    }
  }

  playFileSound(key, path, volume, loop) {
    this.stopCategory(key);

    let audio = this.audioCache[path];
    if (!audio) {
      audio = new Audio(path);
      this.audioCache[path] = audio;
    }
    
    audio.volume = volume;
    audio.loop = loop;
    audio.muted = false;
    audio.currentTime = 0;
    
    audio.play().catch(e => console.log(`Playback prevented for category ${key}:`, e));
    this.playingTracks[key] = audio;
  }

  playSynthSound(key, type, volume, loop) {
    this.initCtx();
    const ctx = this.ctx;
    if (!ctx) return;

    this.stopCategory(key);
    const time = ctx.currentTime;
    
    if (key === 'lobby') {
      let step = 0;
      const chords = [
        [261.63, 329.63, 392.00, 523.25], // C Major
        [293.66, 349.23, 392.00, 587.33], // G Major
        [220.00, 261.63, 329.63, 440.00], // A Minor
        [261.63, 349.23, 440.00, 523.25]  // F Major
      ];
      
      const playNote = () => {
        if (!this.playingTracks['lobby'] || this.isMuted) return;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        const chordIndex = Math.floor(step / 4) % chords.length;
        const noteIndex = step % 4;
        const freq = chords[chordIndex][noteIndex];
        
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, ctx.currentTime);
        gain.gain.setValueAtTime(volume * 0.18, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.4);
        step++;
      };

      this.playingTracks['lobby'] = setInterval(playNote, 250);
      playNote();

    } else if (key === 'background' || key === 'q_countdown') {
      this.kahootPlaying = true;
      const bpm = 125;
      const stepDuration = 60 / bpm / 2;
      let nextNoteTime = ctx.currentTime;
      let step = 0;

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
          this.scheduleKahootNote(step, nextNoteTime, melody[step % 16], bassline[step % 16], volume);
          nextNoteTime += stepDuration;
          step++;
        }
        this.kahootTimeout = setTimeout(scheduler, 25);
      };

      this.playingTracks[key] = {
        stop: () => {
          this.kahootPlaying = false;
          if (this.kahootTimeout) {
            clearTimeout(this.kahootTimeout);
            this.kahootTimeout = null;
          }
        }
      };
      scheduler();

    } else if (key === 'submit') {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(523.25, time);
      osc.frequency.exponentialRampToValueAtTime(1046.50, time + 0.15);
      gain.gain.setValueAtTime(volume * 0.25, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.25);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.3);

    } else if (key === 'correct') {
      const notes = [523.25, 659.25, 783.99, 1046.50];
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, time + idx * 0.08);
        gain.gain.setValueAtTime(volume * 0.2, time + idx * 0.08);
        gain.gain.exponentialRampToValueAtTime(0.001, time + idx * 0.08 + 0.25);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time + idx * 0.08);
        osc.stop(time + idx * 0.08 + 0.3);
      });

    } else if (key === 'wrong') {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(180, time);
      osc.frequency.linearRampToValueAtTime(60, time + 0.6);
      const filter = ctx.createBiquadFilter();
      filter.type = 'lowpass';
      filter.frequency.setValueAtTime(400, time);
      gain.gain.setValueAtTime(volume * 0.3, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.7);
      osc.connect(filter);
      filter.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.85);

    } else if (key === 'timeout') {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(150, time);
      osc.frequency.linearRampToValueAtTime(50, time + 1.0);
      gain.gain.setValueAtTime(volume * 0.5, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 1.0);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 1.2);

    } else if (key === 'countdown') {
      let step = 0;
      const playTick = () => {
        if (!this.playingTracks['countdown'] || this.isMuted) return;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(step % 2 === 0 ? 880 : 440, ctx.currentTime);
        gain.gain.setValueAtTime(volume * 0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
        step++;
      };
      this.playingTracks['countdown'] = setInterval(playTick, 1000);
      playTick();

    } else {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(330, time);
      gain.gain.setValueAtTime(volume * 0.15, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.15);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.2);
    }
  }

  scheduleKahootNote(step, time, melFreq, bassFreq, volume) {
    const ctx = this.ctx;
    if (bassFreq > 0) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(bassFreq, time);
      gain.gain.setValueAtTime(volume * 0.12, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.2);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.25);
    }
    if (melFreq > 0 && (step % 2 === 0 || step % 3 === 0)) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(melFreq, time);
      gain.gain.setValueAtTime(volume * 0.08, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.18);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.2);
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

  stopCategory(key) {
    const track = this.playingTracks[key];
    if (track) {
      if (track instanceof Audio) {
        track.pause();
        track.currentTime = 0;
      } else if (typeof track === 'number') {
        clearInterval(track);
      } else if (track.stop) {
        track.stop();
      }
      delete this.playingTracks[key];
    }
  }

  stopAll(force = false) {
    for (const key in this.playingTracks) {
      this.stopCategory(key);
    }
    this.kahootPlaying = false;
    if (this.kahootTimeout) {
      clearTimeout(this.kahootTimeout);
      this.kahootTimeout = null;
    }
  }

  stopAllQuestionMusic() {
    this.stopCategory('q_countdown');
    this.stopCategory('background');
    this.stopCategory('countdown');
  }

  setMute(mute) {
    this.isMuted = mute;
    if (mute) {
      this.stopAll(true);
    }
  }

  getMute() {
    return this.isMuted;
  }

  reloadTracks() {
    // Fallback reload helper
    fetch('api.php?action=get_quiz_audio_settings&quiz_id=0')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          this.setAudioConfig(data.audio_config);
        }
      });
  }

  playLobby() { this.playCategory('lobby'); }
  playStartSequence() { this.playCategory('start'); }
  playReveal() { this.playCategory('reveal'); }
  playQuestionMusic() { this.playCategory('background'); }
  playLocked() { this.playCategory('submit'); }
  playCorrect() { this.playCategory('correct'); }
  playWrong() { this.playCategory('wrong'); }
  playLeaderboardReveal() { this.playCategory('leaderboard'); }
  playWinner() { this.playCategory('winner'); }
  playTop3Reveal() { this.playCategory('top3'); }
  playTrophy() { this.playCategory('trophy'); }
  playFireworks() { this.playCategory('fireworks'); }
  playConfetti() { this.playCategory('confetti'); }
  playJoin() { this.playCategory('join'); }
  playLeave() { this.playCategory('leave'); }
  playClick() { this.playCategory('click'); }
  playQuizCompletion() { this.playCategory('completion'); }
  playNextQuestion() { this.playCategory('next_question'); }

  playCountdown(timeLeft) {
    if (this.isMuted) {
      this.stopAllQuestionMusic();
      return;
    }
    
    if (timeLeft > 10) {
      this.stopCategory('countdown');
      
      if (this.audioConfig?.categories['q_countdown']?.enabled) {
        if (!this.playingTracks['q_countdown']) {
          this.playCategory('q_countdown');
        }
      } else if (this.audioConfig?.categories['background']?.enabled) {
        if (!this.playingTracks['background']) {
          this.playCategory('background');
        }
      }
    } else if (timeLeft <= 10 && timeLeft > 0) {
      this.stopCategory('q_countdown');
      this.stopCategory('background');
      
      if (this.audioConfig?.categories['countdown']?.enabled) {
        if (!this.playingTracks['countdown']) {
          this.playCategory('countdown');
        }
      }
    } else if (timeLeft === 0) {
      this.stopAllQuestionMusic();
      this.playCategory('timeout');
    }
  }

  stopKBCMusic() {
    this.stopAll(true);
  }
}

window.sound = new SoundSynth();
