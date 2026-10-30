"""NxtGen brag soundtrack: 120 BPM, C major, one beat grid from frame 0. Music and effects mixed together."""
import numpy as np
import wave
import json

MAP = json.load(open('timemap.json'))


def V(t):
    """Story time -> video time (the 60 s cut holds longer)."""
    for (T0, t0), (T1, t1) in zip(MAP, MAP[1:]):
        if t0 <= t <= t1 and t1 > t0:
            return T0 + (T1 - T0) * (t - t0) / (t1 - t0)
    return MAP[-1][0]

SR = 44100
DUR = 60.0
N = int(SR * DUR)
BEAT = 0.5
rng = np.random.default_rng(3)

music = np.zeros((N, 2))
sfx = np.zeros((N, 2))


def hz(midi):
    return 440.0 * 2 ** ((midi - 69) / 12)


def put(buf, t, sig, pan=0.0, gain=1.0):
    i = int(t * SR)
    if i >= N:
        return
    sig = sig[: N - i] * gain
    l, r = np.sqrt(0.5 * (1 - pan)), np.sqrt(0.5 * (1 + pan))
    buf[i : i + len(sig), 0] += sig * l
    buf[i : i + len(sig), 1] += sig * r


def env(n, a=0.005, d=0.3):
    t = np.arange(n) / SR
    return np.minimum(1, t / a) * np.exp(-t / d)


def pluck(midi, dur=0.5, bright=0.5):
    # Karplus-Strong: a warm, ukulele-ish pluck
    f = hz(midi)
    p = int(SR / f)
    n = int(SR * dur)
    buf = rng.uniform(-1, 1, p)
    out = np.zeros(n)
    for i in range(n):
        out[i] = buf[i % p]
        buf[i % p] = (bright * buf[i % p] + (1 - bright) * buf[(i + 1) % p]) * 0.995
    return out * env(n, 0.002, dur * 0.6)


def glock(midi, dur=0.8):
    t = np.arange(int(SR * dur)) / SR
    f = hz(midi)
    s = np.sin(2 * np.pi * f * t) + 0.35 * np.sin(2 * np.pi * f * 2.76 * t) * np.exp(-t / 0.08) + 0.15 * np.sin(2 * np.pi * f * 5.4 * t) * np.exp(-t / 0.04)
    return s * env(len(t), 0.001, dur * 0.35)


def bass(midi, dur=0.45):
    t = np.arange(int(SR * dur)) / SR
    f = hz(midi)
    s = np.sin(2 * np.pi * f * t) + 0.25 * np.sin(2 * np.pi * 2 * f * t)
    return np.tanh(1.4 * s) * env(len(t), 0.004, dur * 0.5)


def kick():
    t = np.arange(int(SR * 0.3)) / SR
    f = 50 + 90 * np.exp(-t / 0.03)
    ph = 2 * np.pi * np.cumsum(f) / SR
    return np.sin(ph) * np.exp(-t / 0.12)


def lowpass(x, a):
    y = np.zeros_like(x)
    acc = 0.0
    for i, v in enumerate(x):
        acc += a * (v - acc)
        y[i] = acc
    return y


def clap():
    n = int(SR * 0.18)
    x = rng.uniform(-1, 1, n)
    x = x - lowpass(x, 0.15)  # take the lows out
    t = np.arange(n) / SR
    e = np.exp(-t / 0.05) * (1 + 0.6 * (np.sin(2 * np.pi * 90 * t) > 0) * (t < 0.03))
    return x * e


def shaker():
    n = int(SR * 0.06)
    x = rng.uniform(-1, 1, n)
    x = x - lowpass(x, 0.4)
    return x * env(n, 0.01, 0.02)


def whoosh(dur=0.45):
    n = int(SR * dur)
    x = rng.uniform(-1, 1, n)
    t = np.arange(n) / SR
    out = np.zeros(n)
    acc = 0.0
    for i in range(n):
        a = 0.02 + 0.25 * (i / n)
        acc += a * (x[i] - acc)
        out[i] = acc
    return out * np.sin(np.pi * t / dur) ** 2 * 2.2


def blip(midi, dur=0.16):
    t = np.arange(int(SR * dur)) / SR
    f = hz(midi) * (1 + 0.5 * np.exp(-t / 0.02))
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(len(t), 0.002, 0.06)


def thup():
    n = int(SR * 0.12)
    x = lowpass(rng.uniform(-1, 1, n), 0.05)
    return x * env(n, 0.003, 0.03) * 4


# ---- chords: C G Am F, one per bar (2 s) ----
prog = [(48, [60, 64, 67]), (43, [59, 62, 67]), (45, [57, 60, 64]), (41, [57, 60, 65])]
bars = int(DUR / 2) + 1
for b in range(bars):
    t0 = b * 2.0
    root, ch = prog[b % 4]
    hook = t0 < 4.0
    outro = t0 >= 50.0
    for k in range(4):  # beats
        tb = t0 + k * BEAT
        if tb >= DUR - 0.3:
            continue
        if outro and tb >= 51.0:
            continue
        if not hook:
            if k == 0:
                put(music, tb, kick(), 0, 0.5)
            if k == 2:
                put(music, tb, clap(), 0.1, 0.16)
            put(music, tb, bass(root if k % 2 == 0 else root + 12, 0.42), 0, 0.30)
        # bouncy offbeat strums
        for j, n in enumerate(ch):
            put(music, tb + 0.25 + j * 0.012, pluck(n, 0.45, 0.55), -0.3 + 0.3 * j, 0.11 if not hook else 0.08)
        for e in range(2):
            put(music, tb + e * 0.25, shaker(), 0.35, 0.05 if not hook else 0.07)
    if hook:  # the hook: nervous staccato plucks, no drums yet
        for k in range(8):
            put(music, t0 + k * 0.25, pluck(ch[k % 3] + 12, 0.18, 0.7), 0.2, 0.06)

# glock motif over the groove (bars from the reveal on)
motif = [(0, 72), (0.5, 76), (0.75, 79), (1.0, 76), (1.5, 74)]
for b in range(3, 30):
    t0 = b * 2.0 - 1.0  # starts at 5.0
    if b % 2 == 0:
        continue  # every other bar: room to breathe
    if t0 + 2 > 51.0:
        break
    root, ch = prog[(b - 1) % 4]
    for dt, m in motif:
        nm = m if b % 2 == 0 else m - 2 if m != 72 else 72
        put(music, t0 + dt, glock(nm, 0.6), 0.25, 0.07)

# outro: C major ring
for j, n in enumerate([48, 60, 64, 67, 72, 76]):
    put(music, 51.0 + j * 0.03, pluck(n, 5.0, 0.5), -0.4 + 0.16 * j, 0.16)
put(music, 51.0, bass(36, 3.5), 0, 0.35)
put(music, 51.0, kick(), 0, 0.6)
for j, n in enumerate([72, 76, 79, 84, 88]):
    put(music, 51.1 + j * 0.25, glock(n, 1.6), 0.2, 0.08)

# ---- effects (in key, under the music) ----
for i in range(7):
    put(sfx, V(0.05 + i * 0.12 + 0.3), thup(), -0.4 + 0.12 * i, 0.22)
for i in range(6):
    put(sfx, V(0.35 + i * 0.13 + 0.3), thup(), 0.3 - 0.1 * i, 0.18)
for t, m in [(0.25, 79), (0.85, 84), (3.6, 84), (5.6, 79), (7.75, 76), (8.25, 79), (9.55, 79), (9.7, 84),
             (10.95, 84), (12.6, 88), (12.85, 76), (13.25, 79), (14.55, 79), (14.7, 84), (16.35, 76), (17.95, 79)]:
    put(sfx, V(t), blip(m), 0.1, 0.12)
for t in [4.85, 9.35, 19.35, 34.35, 42.0, 50.85]:
    put(sfx, t, whoosh(0.45), 0, 0.10)
put(sfx, V(3.1), glock(84, 1.0), 0, 0.12)
put(sfx, V(3.1), kick(), 0, 0.4)
for t in [7.3, 11.0, 11.5, 12.5, 15.1, 15.6, 16.1, 19.4]:  # taps
    put(sfx, V(t), blip(91, 0.05), 0.2, 0.06)
put(sfx, V(10.45), np.sin(2 * np.pi * hz(88) * np.arange(int(SR * 0.12)) / SR) * env(int(SR * 0.12), 0.003, 0.2), 0.3, 0.07)  # scan beep (E6)
for j, n in enumerate([72, 76, 79]):  # "Checked in" chime
    put(sfx, V(11.75) + j * 0.06, glock(n + 12, 0.7), 0, 0.09)
for j, n in enumerate([72, 76, 79, 84]):  # "Given" chime
    put(sfx, V(19.6) + j * 0.07, glock(n + 12, 0.9), 0.1, 0.10)

mix = music + 0.85 * sfx
# gentle fade-out over the last 0.6 s
fade = np.ones(N)
fade[-int(SR * 2.5):] = np.linspace(1, 0, int(SR * 2.5)) ** 1.5
mix *= fade[:, None]
mix = np.tanh(mix / np.max(np.abs(mix)) * 1.3) / np.tanh(1.3) * 0.89
pcm = (mix * 32767).astype(np.int16)
with wave.open('soundtrack.wav', 'wb') as w:
    w.setnchannels(2)
    w.setsampwidth(2)
    w.setframerate(SR)
    w.writeframes(pcm.tobytes())
print('ok', mix.shape)
