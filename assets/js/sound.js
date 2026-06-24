class SoundSynth {
  constructor() {
    this.ctx = null;
    this.audioConfig = null;
    this.playingTracks = {};
    this.isMuted = false;
    this.audioCache = {};
    this.hasPlayedTimeout = false;

    console.log("[SoundSynth] Initializing audio manager...");
    this.reloadTracks();
  }

  setAudioConfig(config) {
    if (!config) return;
    this.audioConfig = config;
    this.isMuted = !!config.global.mute_all;
    console.log("[SoundSynth] Configuration updated. Mute state:", this.isMuted);

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
    const musicKeys = ['lobby', 'start', 'countdown', 'leaderboard'];
    return musicKeys.includes(key) ? 'music' : 'effect';
  }

  playCategory(key) {
    console.log(`[SoundSynth] Triggered playCategory for: "${key}"`);
    if (!this.audioConfig) {
      console.warn(`[SoundSynth] Config not loaded yet for category: ${key}`);
      return;
    }

    const catConfig = this.audioConfig.categories[key];
    if (!catConfig) {
      console.warn(`[SoundSynth] Category config missing for: ${key}`);
      return;
    }
    if (!catConfig.enabled) {
      console.log(`[SoundSynth] Category is disabled in settings: ${key}`);
      return;
    }
    if (this.isMuted) {
      console.log(`[SoundSynth] Playback skipped because mute is enabled: ${key}`);
      return;
    }

    const path = catConfig.file_path;
    const volume = this.resolveVolume(key);
    if (volume <= 0) {
      console.log(`[SoundSynth] Playback skipped due to zero resolved volume for: ${key}`);
      return;
    }

    console.log(`[SoundSynth] Playing: "${key}" | Path: "${path}" | Volume: ${volume}`);
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
    
    audio.play().catch(e => console.error(`[SoundSynth] Audio element playback failed for ${key}:`, e));
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

    } else if (key === 'start') {
      // Fanfare: rising major chord sequence
      const notes = [261.63, 329.63, 392.00, 523.25];
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, time + idx * 0.1);
        gain.gain.setValueAtTime(volume * 0.2, time + idx * 0.1);
        gain.gain.exponentialRampToValueAtTime(0.001, time + idx * 0.1 + 0.3);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time + idx * 0.1);
        osc.stop(time + idx * 0.1 + 0.35);
      });

    } else if (key === 'next_question') {
      // Simple double-beep high chime
      [587.33, 880.00].forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, time + idx * 0.08);
        gain.gain.setValueAtTime(volume * 0.15, time + idx * 0.08);
        gain.gain.exponentialRampToValueAtTime(0.001, time + idx * 0.08 + 0.2);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time + idx * 0.08);
        osc.stop(time + idx * 0.08 + 0.25);
      });

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

    } else if (key === 'leaderboard') {
      // Grand victory theme: sweeping upward notes
      const notes = [329.63, 392.00, 523.25, 659.25, 783.99];
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, time + idx * 0.15);
        gain.gain.setValueAtTime(volume * 0.25, time + idx * 0.15);
        gain.gain.exponentialRampToValueAtTime(0.001, time + idx * 0.15 + 0.5);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(time + idx * 0.15);
        osc.stop(time + idx * 0.15 + 0.6);
      });
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
      console.log(`[SoundSynth] Stopped category: "${key}"`);
    }
  }

  stopAll(force = false) {
    console.log(`[SoundSynth] Stopping all sounds (force: ${force})...`);
    for (const key in this.playingTracks) {
      this.stopCategory(key);
    }
  }

  setMute(mute) {
    this.isMuted = mute;
    console.log(`[SoundSynth] Mute set to: ${mute}`);
    if (mute) {
      this.stopAll(true);
    }
  }

  getMute() {
    return this.isMuted;
  }

  reloadTracks() {
    fetch('api.php?action=get_quiz_audio_settings&quiz_id=0')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          this.setAudioConfig(data.audio_config);
        }
      }).catch(e => console.error('[SoundSynth] Error loading config:', e));
  }

  // 9 Explicit Triggers
  playLobby() { 
    this.playCategory('lobby'); 
  }
  
  playStart() { 
    this.hasPlayedTimeout = false;
    this.stopAll();
    this.playCategory('start'); 
  }

  playNextQuestion() { 
    this.hasPlayedTimeout = false;
    this.stopAll();
    this.playCategory('next_question'); 
  }

  playCountdown(timeLeft, isInitialSync = false) {
    if (this.isMuted) {
      this.stopCategory('countdown');
      return;
    }
    
    if (timeLeft <= 10 && timeLeft > 0) {
      if (!this.playingTracks['countdown']) {
        this.playCategory('countdown');
      }
    } else {
      this.stopCategory('countdown');
      if (timeLeft === 0 && !this.hasPlayedTimeout) {
        this.hasPlayedTimeout = true;
        if (!isInitialSync) {
          this.playCategory('timeout');
        }
      }
    }
  }

  stopCountdown() {
    this.stopCategory('countdown');
  }

  playLocked() { 
    this.playCategory('submit'); 
  }

  playCorrect() { 
    this.playCategory('correct'); 
  }

  playWrong() { 
    this.playCategory('wrong'); 
  }

  playTimeout() {
    this.hasPlayedTimeout = true;
    this.playCategory('timeout');
  }

  playLeaderboard() { 
    this.stopAll(true);
    this.playCategory('leaderboard'); 
  }

  // Aliases for compatibility
  playStartSequence() { this.playStart(); }
  playLockedSound() { this.playLocked(); }
  stopKBCMusic() { this.stopCountdown(); }
  stopAllQuestionMusic() { this.stopCountdown(); }
}

window.sound = new SoundSynth();
