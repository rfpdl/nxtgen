# 05 — Satellites in the address
Status: done 2026-11-08
Spec items 5, 6, 10. Staff routes lose `{church:slug}` and become `/{satellite}/serve/…` and `/{satellite}/admin/…`;
parents `/family/…`. Seeds CCF with `dubai`, `sharjah`, `abu-dhabi`. The base church feature checks the satellite in
the address: Admin pages need the Admin Role there (or the class admin's pages); Lifeshaper only → that satellite's
Serve; no Role there → 404. Done when: an arch test finds no route with a church slug and every staff route carries
`{satellite}`; a Dubai admin opening Sharjah's Admin gets 404; every existing e2e journey passes at the new addresses.
