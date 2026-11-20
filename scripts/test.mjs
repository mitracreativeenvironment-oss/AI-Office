// MCE System Logic Test: node scripts/test.mjs
import assert from 'node:assert/strict';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';
import { runnerImport } from '../frontend/node_modules/vite/dist/node/index.js';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', 'frontend');
const O = (await runnerImport(path.join(root, 'src/office.ts'), { root, configFile: false, cacheDir: path.join(os.tmpdir(), 'kantor-vite'), logLevel: 'error' })).module;

let n = 0;
const t = (name, fn) => {
  fn();
  n++;
};

t('jadwal operasional MCE: kerja, laporan, belajar, siaga, dan libur', () => {
  // Director AI evaluasi target
  assert.equal(O.agentDoing('director', 1, '07:35').activity, 'kerja');
  assert.equal(O.agentDoing('director', 1, '07:35').label, 'evaluasi target harian…');

  // Tech Head sprint dev
  assert.equal(O.agentDoing('tech_head', 1, '08:10').activity, 'kerja');

  // Seluruh agen laporan sore
  assert.equal(O.agentDoing('dev_ai', 1, '16:15').activity, 'laporan');

  // Seluruh agen belajar malam
  assert.equal(O.agentDoing('academy_head', 6, '20:30').activity, 'belajar');

  // Siap menerima perintah (siaga) di jam kerja
  assert.equal(O.agentDoing('director', 1, '11:00').activity, 'siaga');

  // Di luar jam kantor (malam/pulang)
  assert.equal(O.agentDoing('director', 1, '23:00').activity, 'pulang');

  // Akhir pekan (hari 6 = Sabtu)
  assert.equal(O.agentDoing('tech_head', 6, '08:10').activity, 'pulang');
  assert.equal(O.agentDoing('tech_head', 6, '08:10', true).activity, 'kerja');
});

t('timelapse tiga babak berulang (30 detik)', () => {
  assert.equal(O.LAPSE_SECONDS, 30);
  assert.equal(O.lapseAt(0).time, '07:28');
  assert.equal(O.lapseAt(15).act, 1);
  assert.equal(O.lapseAt(30).time, '07:28');
});

t('agen santai: pergerakan koridor ke sofa, kopi, dan Mission Board', () => {
  const seen = new Set();
  for (let x = 0; x < 48; x += 0.5) seen.add(O.idleAt('director', x).spot);
  assert.ok(seen.has('meja') && seen.size >= 3);
});

t('meja agen dan integrasi Mission Board MCE', () => {
  const desks = O.officeDesks(1, '10:00');
  assert.equal(desks.length, 6);
  assert.ok(desks.every((d) => d.bubble));
  const board = O.missionBoard(desks);
  assert.ok(board.items.length >= 4);
});

t('parameter URL (?timelapse=1&jam=08.10)', () => {
  assert.deepEqual(O.params(new URLSearchParams('timelapse=1&rekam=1&jam=08.10')), {
    lapse: true,
    rec: true,
    time: '08:10',
    alwaysWork: true,
  });
});

const G = (await runnerImport(path.join(root, 'src/gaya/daftar.ts'), { root, configFile: false, cacheDir: path.join(os.tmpdir(), 'kantor-vite'), logLevel: 'error' })).module;

t('17 variasi tema 3D terdaftar & fallback aman', () => {
  assert.equal(G.DAFTAR_GAYA.length, 17);
  assert.equal(new Set(G.DAFTAR_GAYA.map((g) => g.id)).size, 17);
  assert.equal(G.DAFTAR_GAYA.filter((g) => g.jenis === 'paduan').length, 9);
  assert.equal(G.gayaAwal('clay', 'voxel', 'asli'), 'clay');
  assert.equal(G.gayaAwal('ngawur', 'voxel', 'asli'), 'voxel');
  assert.equal(G.gayaAwal(null, null, 'taman'), 'taman');
  assert.equal(G.cariGaya('ngawur').id, 'asli');
});

console.log(`\n🎉 SUKSES: Semua ${n} tes logika MCE lulus!\n`);
