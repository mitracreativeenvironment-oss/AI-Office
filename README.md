# AI-Office

Repository untuk project AI-Office oleh `mitracreativeenvironment-oss`.

## Otomatisasi Release & Tagging

Repository ini dilengkapi dengan GitHub Actions workflow (`.github/workflows/auto-release-tag.yml`) yang mengatur perilisan secara otomatis:

1. **Perubahan Versi (Version Change)**:
   - Jika Anda mengubah isi file `VERSION` (contoh: dari `0.1.0` ke `0.1.1` atau `1.0.0`) atau mengubah `version` di `package.json`, workflow akan secara otomatis:
     - Membuat **Git Tag** baru (misalnya `v0.1.1`).
     - Mem-publish **Official GitHub Release** dengan auto-generated release notes.
2. **Perubahan Non-Versi (Non-Version Change)**:
   - Jika ada commit biasa di branch `main` (misal update fitur, dokumentasi, bugfix tanpa mengubah versi), workflow akan secara otomatis:
     - Memperbarui **Rolling Release** (`rolling-release`) di GitHub Releases sebagai pre-release dengan commit info terbaru.
     - Tidak mengotori git tags dengan tag versi baru.

---

## Koneksi Repository & Deploy Key

Koneksi lokal menggunakan SSH Deploy Key yang terisolasi khusus untuk repository ini:

- **Private Key**: `.deploy_key/ai_office_deploy_key` (Tersimpan lokal, di-ignore oleh `.gitignore`)
- **Public Key**: `.deploy_key/ai_office_deploy_key.pub`
- **Remote URL**: `git@github.com:mitracreativeenvironment-oss/AI-Office.git`

### Konfigurasi Git SSH Lokal
Git telah dikonfigurasi untuk otomatis menggunakan key ini:
```bash
git config core.sshCommand "ssh -i F:/AI-Office/.deploy_key/ai_office_deploy_key -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
```

### Cara Memasang Deploy Key di GitHub
1. Buka: [GitHub Deploy Keys Settings](https://github.com/mitracreativeenvironment-oss/AI-Office/settings/keys)
2. Klik **Add deploy key**
3. Masukkan Title: `AI-Office Deploy Key`
4. Tempelkan isi dari `.deploy_key/ai_office_deploy_key.pub`
5. Centang **"Allow write access"** agar bisa melakukan push.
6. Klik **Add key**.

### Izin GitHub Actions (Workflow Permissions)
Agar GitHub Actions bisa membuat tag dan release otomatis:
1. Buka: `Repository Settings -> Actions -> General`
2. Pada bagian **Workflow permissions**, pilih **Read and write permissions**.
3. Klik **Save**.
