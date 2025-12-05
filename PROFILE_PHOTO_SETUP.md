# 📸 Profil Fotoğrafı ve Instructor Fotoğrafı Sistemi

## ✅ Tamamlanan Özellikler

### 1. **Tüm Kullanıcılar İçin Profil Fotoğrafı**
- ✅ User, Instructor, Admin - herkes fotoğraf yükleyebilir
- ✅ Profile sayfasının başında büyük profil fotoğrafı görünür
- ✅ Fotoğraf yöklenmezse emoji gösterilir (📷)
- ✅ Max 5MB, PNG/JPG/GIF/WebP formatları destekleniyor
- ✅ Fotoğraf veritabanında BLOB olarak saklanıyor

### 2. **Derslerde Instructor Fotoğrafı**
- ✅ Ders oluştururken instructor fotoğrafı yüklenebiliyor
- ✅ Seçilmezse, öğretmenin profil fotoğrafı otomatik kullanılır
- ✅ Derslerde öğretmen adının yanında fotoğrafı görünür
- ✅ Anasayfa (index.php) - gelecek dersler
- ✅ Anasayfa (index.php) - geçmiş dersler

### 3. **Veritabanı Değişiklikleri**
```sql
-- Users tablosuna eklendi (zaten vardı):
- profile_photo (LONGBLOB)

-- Classes tablosuna eklendi:
- instructor_photo (LONGBLOB)
```

### 4. **Dosya Güncellemeleri**

#### `profile.php`
- Profil kartının başında fotoğraf gösterme
- Tüm kullanıcılar fotoğraf yükleyebilir
- Renkli buton (📤 Fotoğrafı Yükle)

#### `admin.php`
- Ders oluştururken instructor_photo alanı
- `enctype="multipart/form-data"` form
- Fotoğraf seçilmezse, trainer'ın profil fotoğrafı otomatik kullanılır

#### `index.php`
- Derslerde instructor fotoğrafı gösteriliyor
- Önce class'ın instructor_photo'su, yoksa users'ın profile_photo'su kullanılıyor

#### `style.css`
- `.profile-photo-display` - profil fotoğraf stili
- `.instructor-photo-card` - instructor fotoğraf kartı

## 🚀 Nasıl Kullanılır

### Profil Fotoğrafı Yükleme (Tüm Kullanıcılar)
1. Profilime git (`profile.php`)
2. "👤 Profil Fotoğrafı" kartında fotoğraf seç
3. "📤 Fotoğrafı Yükle" butonuna tıkla
4. Fotoğraf kaydedilir ve anında görünür

### Ders Oluştururken Instructor Fotoğrafı
1. Admin Panel → Yeni Ders Oluştur
2. Form doldur
3. "Instructor Photo" alanında fotoğraf seç (opsiyonel)
4. Seçilmezse, öğretmenin profil fotoğrafı kullanılır

## 📊 Test Adresleri

- **Profil Sayfası**: `http://localhost/fitness-booking-system/profile.php`
- **Admin Panel**: `http://localhost/fitness-booking-system/admin.php`
- **Anasayfa**: `http://localhost/fitness-booking-system/index.php`

## 🔐 Güvenlik

- ✅ Dosya tipi kontrolü (MIME type)
- ✅ Dosya boyutu kontrolü (5MB max)
- ✅ SQL Injection koruması (mysqli_real_escape_string)
- ✅ Base64 encoding (binary image veritabanına kaydediliyor)

## 💾 Veritabanı Tabloları

```
users:
  - id (INT)
  - username (VARCHAR)
  - email (VARCHAR)
  - password (VARCHAR)
  - profile_photo (LONGBLOB) ← Profil fotoğrafı
  - role (ENUM: user, instructor, admin)
  - ...

classes:
  - id (INT)
  - title (VARCHAR)
  - trainer_name (VARCHAR)
  - instructor_photo (LONGBLOB) ← Instructor fotoğrafı
  - description (TEXT)
  - date_time (DATETIME)
  - ...
```

## 📝 Notlar

- Fotoğraflar veritabanında LONGBLOB olarak saklanıyor
- Base64 encoding ile HTML'de görüntüleniyor
- Sınırlı dosya boyutu (5MB) sunucuyu koruyor
- Gereksiz yere büyük veritabanı dosyası oluşturmuyor

## ✨ Geliştirebilecek Alanlar

- [ ] Fotoğraf boyutlandırma (compression)
- [ ] Crop özelliği eklemek
- [ ] Profil fotoğrafını kırpma
- [ ] Batch fotoğraf yükleme
- [ ] CDN'de saklama (opsiyonel)
