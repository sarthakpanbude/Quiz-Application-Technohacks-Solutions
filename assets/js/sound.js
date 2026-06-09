class SoundSynth {
  constructor() {
    this.ctx = null;
    this.lobbyInterval = null;
    this.isMuted = false;
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
    if (mute) {
      this.stopAll();
    }
  }

  getMute() {
    return this.isMuted;
  }

  playLobby() {
    this.stopAll();
    if (this.isMuted) return;
    this.initCtx();
    const ctx = this.ctx;
    
    let step = 0;
    const notes = [261.63, 293.66, 329.63, 349.23, 392.00, 349.23, 329.63, 293.66]; // C4 D4 E4 F4 G4 F4 E4 D4 arpeggio
    
    const playNote = () => {
      if (this.isMuted || !this.ctx) return;
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      
      osc.type = 'triangle';
      osc.frequency.setValueAtTime(notes[step % notes.length], ctx.currentTime);
      
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
      
      osc.connect(gain);
      gain.connect(ctx.destination);
      
      osc.start();
      osc.stop(ctx.currentTime + 0.4);
      
      step++;
    };

    playNote();
    this.lobbyInterval = setInterval(playNote, 400);
  }

  startKBCDrone() {
    if (this.isMuted || this.kbcDroneOsc) return;
    this.initCtx();
    const ctx = this.ctx;

    this.kbcDroneOsc = ctx.createOscillator();
    this.kbcDroneGain = ctx.createGain();
    
    this.kbcDroneOsc.type = 'sawtooth';
    this.kbcDroneOsc.frequency.setValueAtTime(55, ctx.currentTime); // Low tense note

    // Filter to make it dark and moody
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(150, ctx.currentTime);

    this.kbcDroneGain.gain.setValueAtTime(0, ctx.currentTime);
    this.kbcDroneGain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 2); // Fade in

    this.kbcDroneOsc.connect(filter);
    filter.connect(this.kbcDroneGain);
    this.kbcDroneGain.connect(ctx.destination);

    this.kbcDroneOsc.start();
  }

  stopKBCDrone() {
    if (this.kbcDroneGain && this.ctx) {
      this.kbcDroneGain.gain.linearRampToValueAtTime(0, this.ctx.currentTime + 0.5);
      setTimeout(() => {
        if (this.kbcDroneOsc) {
          try { this.kbcDroneOsc.stop(); } catch(e){}
          this.kbcDroneOsc = null;
        }
      }, 500);
    }
  }

  playCountdown(timeLeft) {
    if (this.isMuted) return;
    this.initCtx();
    const ctx = this.ctx;

    this.startKBCDrone();

    // Heartbeat low drum (KBC style)
    const oscLow = ctx.createOscillator();
    const gainLow = ctx.createGain();
    oscLow.type = 'sine';
    oscLow.frequency.setValueAtTime(60, ctx.currentTime);
    oscLow.frequency.exponentialRampToValueAtTime(30, ctx.currentTime + 0.5);
    gainLow.gain.setValueAtTime(0.9, ctx.currentTime);
    gainLow.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
    oscLow.connect(gainLow);
    gainLow.connect(ctx.destination);
    oscLow.start();
    oscLow.stop(ctx.currentTime + 0.6);

    // High tension tick
    const oscHigh = ctx.createOscillator();
    const gainHigh = ctx.createGain();

    if (timeLeft <= 5 && timeLeft > 0) {
      // Faster, more intense tick for last 5 seconds
      oscHigh.type = 'square';
      oscHigh.frequency.setValueAtTime(1000, ctx.currentTime);
      gainHigh.gain.setValueAtTime(0.3, ctx.currentTime);
      gainHigh.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
      
      const oscHigh2 = ctx.createOscillator();
      const gainHigh2 = ctx.createGain();
      oscHigh2.type = 'square';
      oscHigh2.frequency.setValueAtTime(1200, ctx.currentTime + 0.2);
      gainHigh2.gain.setValueAtTime(0.3, ctx.currentTime + 0.2);
      gainHigh2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
      oscHigh2.connect(gainHigh2);
      gainHigh2.connect(ctx.destination);
      oscHigh2.start(ctx.currentTime + 0.2);
      oscHigh2.stop(ctx.currentTime + 0.4);

    } else if (timeLeft === 0) {
      this.stopKBCDrone();
      // KBC Time's up buzzer
      oscHigh.type = 'sawtooth';
      oscHigh.frequency.setValueAtTime(150, ctx.currentTime);
      oscHigh.frequency.linearRampToValueAtTime(50, ctx.currentTime + 1.0);
      gainHigh.gain.setValueAtTime(0.7, ctx.currentTime);
      gainHigh.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.0);
    } else {
      // Normal KBC tick
      oscHigh.type = 'triangle';
      oscHigh.frequency.setValueAtTime(900, ctx.currentTime);
      gainHigh.gain.setValueAtTime(0.2, ctx.currentTime);
      gainHigh.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
    }

    oscHigh.connect(gainHigh);
    gainHigh.connect(ctx.destination);
    oscHigh.start();
    oscHigh.stop(ctx.currentTime + (timeLeft === 0 ? 1.5 : 0.6));
  }

  playVictory() {
    this.stopAll();
    if (this.isMuted) return;
    this.initCtx();
    const ctx = this.ctx;

    const notes = [261.63, 329.63, 392.00, 523.25, 659.25, 783.99, 1046.50]; // Triumphant major scale rollup
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

  stopAll() {
    if (this.lobbyInterval) {
      clearInterval(this.lobbyInterval);
      this.lobbyInterval = null;
    }
  }
}

window.sound = new SoundSynth();
