# NxtGen: brag plan

**What it is:** the kids church app for CCF Dubai. Parents register the family, Lifeshapers check children in at the door, and admins keep name cards moving.
**For:** busy Saturday volunteers and proud parents.
**Angle:** paper chaos at the door → one calm, bright app. Saturday-morning cartoon pops around a trusted church desk.
**Hook:** "Saturday. 1 PM. 80 kids at the door." over a tumbling pile of sign-in sheets and lost name cards.
**Share caption:** "Check-in, sorted. Every kid known, every Saturday."

## Source material (all real)
- Screens captured from the running app (phone 390×844 @3x, desk 1280×800 @2x) against a separate seeded DB `nxtgen_brag_e2e` on port 3022. Nothing touched the e2e or dev data.
- Copy from the app: "My family", "Level 1/2", "Scan card", "Check in 1 child", "Checked in Amara and Ben.", "Who's in", "2 of 2 present", "Confirmed … by Lifeshaper", "New card…", "Lost", "Card: Asked · not paid", "Print list · 3 cards", "Given", "Ben's new card is given."
- Names: only Amara, Ben, Reyes, Ocampo (plus the parent names Ana and Maria). Barcode **AMARAJOYREYES**: the seeded Amara Reyes's own card, so the scanned card and the child who ticks in match. (ADAMMATTHIASABERGOS is the glossary's example and isn't a demo child.)

## Look
Lexend (the app's font). Cream paper #FFF8E6, the bar's amber #F2B32A, ink #2B1D00, action green #14611F with its press edge, Level chart dots (blue #12A3DC, yellow #F2C200, red #E1262C, green #2A9D3A), and the logo's cyan and orange. Chunky outlines with a hard offset shadow, like the app's pressable buttons. Overshoot pops, confetti in Level colours.

## Music
120 BPM (beat 0.5 s), C major, bouncy: plucked ukulele-ish chords, glockenspiel, soft kick and clap, round bass. Effects are tuned to the key: pops on reveals, a two-note scan beep, a tick ding, a cash-desk "given" chime. Every cut lands on a beat.

## Storyboard (25.0 s, 30 fps)
| # | Time | Scene | Caption (held) |
|---|---|---|---|
| 1 Hook | 0.0–3.0 | Paper sign-in sheets and name cards tumble in and jitter | "Saturday. 1 PM." (0.3) + "80 kids at the door." (0.9), held to 3.0 |
| 2 Reveal | 3.0–5.5 | Papers swept off; the NxtGen mark pops, wordmark slides | "Check-in, sorted." 3.6–5.5 |
| 3 Register | 5.5–9.5 | Phone: register form scrolls (Maria Ocampo, Amara, Ben) → "My family" with Level 2 / Level 1 pills popping | "A whole family, in a minute." 5.8–9.5 |
| 4 Check in | 9.5–14.5 | Amara's name card slides under a scan line (AMARAJOYREYES) → door: Amara ticked → "Checked in Amara and Ben." → Who's in, Squads, head count confirmed | "Scan." "Tick." "Everyone counted." built up, all held to 14.5 |
| 5 Lost card | 14.5–20.5 | Ben's card: "New card…" → Lost → "Card: Asked · not paid" → laptop Cards: "Print list · 3 cards" → phone: "Given" → "Ben's new card is given." | "Lost card? Back in their hands by next week." 14.8–20.5 |
| 6 Outro | 20.5–25.0 | Logo holds, confetti settles | "NxtGen: every kid known, every Saturday." |

## 60 s cut (2026-10-30, Roy: "too fast, we can't appreciate it")
Same story and animations at their own speed; the holds between them are longer (`work/timemap.json` maps video time to story time). Scenes: hook 0–5, reveal 5–9.5, register 9.5–19.5, check in 19.5–34.5, name cards 34.5–51, outro 51–60. The music is the same cue in a half-time groove, and every cut still lands on a beat.
Song for later: Roy's favourite (https://www.youtube.com/watch?v=jVx88AJVjkg). Not used yet; it needs the rights and an audio file first.
