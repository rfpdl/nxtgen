# 07 — Registration by slug or code; QR posters
Status: ready-for-agent
Spec items 7, 8. Each satellite gets a code (nanoid) that never changes. `/{slug or code}/register` opens the form; the
code forwards to the slug address. The poster QR carries the code; Link QRs carry the link itself. Done when: a code
URL lands on `/dubai/register`; after a slug change the old code still lands on the new slug.
