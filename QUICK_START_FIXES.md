# ⚡ QUICK START - IMPLEMENTASI FIX SISA

Panduan cepat untuk implementasi perbaikan pada 8 files yang masih pending.

---

## 🚀 LANGKAH CEPAT (5 Menit per File)

### 1. Add Include di Awal Setiap File
```php
// TAMBAH BARIS INI di bawah include config.php
include(__DIR__ . '/../../includes/admin_functions_secure.php');
```

### 2. Replace Query Dengan Prepared Statements

**BEFORE:**
```php
$result = mysqli_query($koneksi, "SELECT * FROM users WHERE id=$id");
```

**AFTER:**
```php
$result = admin_query_fetch_one($koneksi, 
    "SELECT * FROM users WHERE id = ?", 
    [$id], 'i');
```

### 3. Replace Escape String Dengan Validation

**BEFORE:**
```php
$username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
```

**AFTER:**
```php
$username = admin_validate_string($_POST['username'] ?? '', 1, 100);
if (!$username) {
    $message = 'Username tidak valid';
}
```

---

## 📋 FILE-BY-FILE QUICK FIXES

### `proses_hapus_penjual.php`

**Key Changes (Copy-Paste Ready):**

```php
<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Validate CSRF for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: manajemen_penjual.php?error=Invalid+CSRF+token');
        exit();
    }
    $id_user = admin_validate_id($_POST['id'] ?? 0);
} else {
    $id_user = admin_validate_id($_GET['id'] ?? 0);
}

if (!$id_user) {
    header('Location: manajemen_penjual.php?error=Data+tidak+valid');
    exit();
}

try {
    // Fetch penjual data
    $penjual = admin_query_fetch_one($koneksi,
        "SELECT id_user, id_kantin, foto_profil FROM users WHERE id_user = ? AND role = 'penjual' LIMIT 1",
        [$id_user], 'i');
    
    if (!$penjual) {
        header('Location: manajemen_penjual.php?error=Penjual+tidak+ditemukan');
        exit();
    }
    
    $id_kantin = (int)($penjual['id_kantin'] ?? 0);
    $foto_profil = $penjual['foto_profil'];
    
    // Get kantin files
    $kantin = null;
    if ($id_kantin > 0) {
        $kantin = admin_query_fetch_one($koneksi,
            "SELECT logo, banner FROM kantin WHERE id_kantin = ? LIMIT 1",
            [$id_kantin], 'i');
    }
    
    // Delete dengan transaction
    admin_execute_transaction($koneksi, function($koneksi) use ($id_user, $id_kantin) {
        if ($id_kantin > 0) {
            admin_query_execute($koneksi,
                "DELETE FROM keranjang WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)",
                [$id_kantin], 'i');
            
            admin_query_execute($koneksi,
                "DELETE FROM favorit WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)",
                [$id_kantin], 'i');
            
            admin_query_execute($koneksi,
                "DELETE FROM menu WHERE id_kantin = ?",
                [$id_kantin], 'i');
            
            admin_query_execute($koneksi,
                "DELETE FROM kantin WHERE id_kantin = ?",
                [$id_kantin], 'i');
        }
        
        admin_query_execute($koneksi,
            "DELETE FROM users WHERE id_user = ? AND role = 'penjual'",
            [$id_user], 'i');
        
        admin_log_action($koneksi, $_SESSION['id_user'], 'DELETE', 'users', $id_user,
            "Deleted penjual and kantin");
    });
    
    // Cleanup files
    if ($foto_profil) {
        admin_delete_file(__DIR__ . '/../../uploads/profil/' . $foto_profil);
    }
    if ($kantin) {
        if ($kantin['logo']) {
            admin_delete_file(__DIR__ . '/../../uploads/logo/' . $kantin['logo']);
        }
        if ($kantin['banner']) {
            admin_delete_file(__DIR__ . '/../../uploads/banner/' . $kantin['banner']);
        }
    }
    
    header('Location: manajemen_penjual.php?success=hapus');
    exit();
    
} catch (Throwable $e) {
    error_log('Delete penjual error: ' . $e->getMessage());
    header('Location: manajemen_penjual.php?error=' . urlencode('Gagal menghapus penjual'));
    exit();
}
?>
```

---

### `proses_hapus_user.php`

**Key Changes:**

```php
<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: manajemen_user.php?error=Invalid+CSRF+token');
        exit();
    }
    $id_user = admin_validate_id($_POST['id'] ?? 0);
} else {
    $id_user = admin_validate_id($_GET['id'] ?? 0);
}

if (!$id_user) {
    header('Location: manajemen_user.php?error=Data+tidak+valid');
    exit();
}

try {
    // Fetch user data
    $user = admin_query_fetch_one($koneksi,
        "SELECT id_user, foto_profil FROM users WHERE id_user = ? AND role = 'pembeli' LIMIT 1",
        [$id_user], 'i');
    
    if (!$user) {
        header('Location: manajemen_user.php?error=User+tidak+ditemukan');
        exit();
    }
    
    // Delete dengan cascade
    admin_execute_transaction($koneksi, function($koneksi) use ($id_user) {
        admin_query_execute($koneksi,
            "DELETE FROM keranjang WHERE id_user = ?", [$id_user], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM favorit WHERE id_user = ?", [$id_user], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM ulasan WHERE id_user = ?", [$id_user], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM pesanan WHERE id_user = ?", [$id_user], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM transaksi WHERE id_user = ?", [$id_user], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM users WHERE id_user = ? AND role = 'pembeli'",
            [$id_user], 'i');
    });
    
    // Cleanup files
    if ($user['foto_profil']) {
        admin_delete_file(__DIR__ . '/../../uploads/profil/' . $user['foto_profil']);
    }
    
    header('Location: manajemen_user.php?success=hapus');
    exit();
    
} catch (Throwable $e) {
    error_log('Delete user error: ' . $e->getMessage());
    header('Location: manajemen_user.php?error=Gagal+menghapus+user');
    exit();
}
?>
```

---

### `edit_penjual.php` - Form Processing Part

**KEY FIX untuk Password Handling:**

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'CSRF token invalid';
        $message_type = 'error';
    } else {
        // Validate inputs
        $username = admin_validate_string($_POST['username'] ?? '', 3, 100);
        $email = admin_validate_email($_POST['email'] ?? '');
        $password = admin_validate_string($_POST['password'] ?? '', 6, 255);
        $id_kantin = !empty($_POST['id_kantin']) ? admin_validate_id($_POST['id_kantin']) : null;
        
        if (!$username || !$email) {
            $message = 'Validasi gagal';
            $message_type = 'error';
        } else {
            try {
                // Check duplicate (exclude self)
                if (admin_value_exists($koneksi, 'users', 'username', $username, $id_user, 'id_user') ||
                    admin_value_exists($koneksi, 'users', 'email', $email, $id_user, 'id_user')) {
                    $message = 'Username atau email sudah digunakan';
                    $message_type = 'error';
                } else {
                    admin_execute_transaction($koneksi, function($koneksi) use ($username, $email, $password, $id_kantin, $id_user) {
                        // Update without password if empty
                        if ($password) {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            admin_query_execute($koneksi,
                                "UPDATE users SET username = ?, email = ?, password = ?, id_kantin = ? WHERE id_user = ? AND role = 'penjual'",
                                [$username, $email, $hash, $id_kantin, $id_user],
                                'sssii');
                        } else {
                            admin_query_execute($koneksi,
                                "UPDATE users SET username = ?, email = ?, id_kantin = ? WHERE id_user = ? AND role = 'penjual'",
                                [$username, $email, $id_kantin, $id_user],
                                'ssii');
                        }
                        
                        // Update kantin owner if applicable
                        if ($id_kantin) {
                            admin_query_execute($koneksi,
                                "UPDATE kantin SET id_user = ? WHERE id_kantin = ?",
                                [$id_user, $id_kantin],
                                'ii');
                        }
                    });
                    
                    $message = 'Data penjual berhasil diperbarui';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error: ' . htmlspecialchars($e->getMessage());
                $message_type = 'error';
            }
        }
    }
}
```

---

## 🔄 PATTERN UNTUK SEMUA FILE

### Form dengan CSRF Token
```php
<form method="POST">
    <?= admin_csrf_token_field() ?>
    <!-- rest of form -->
</form>
```

### List dengan Pagination
```php
<?php
$page = admin_validate_id($_GET['page'] ?? 1) ?: 1;
$paging = admin_pagination_calc($total, 10, $page);

// Render pagination links
foreach (range(1, $paging['total_pages']) as $p) {
    echo '<a href="?page=' . $p . '">' . $p . '</a>';
}
?>
```

### Search dengan Prepared Statements
```php
<?php
$search = admin_validate_string($_GET['search'] ?? '', 0);

if ($search) {
    $search_term = '%' . $search . '%';
    $results = admin_query_fetch_all($koneksi,
        "SELECT * FROM table WHERE field LIKE ? ORDER BY id DESC",
        [$search_term],
        's');
} else {
    $results = admin_query_fetch_all($koneksi,
        "SELECT * FROM table ORDER BY id DESC",
        [],
        '');
}
?>
```

---

## ✅ VALIDATION QUICK REFERENCE

```php
// String (min 1, max 100)
$var = admin_validate_string($_POST['field'] ?? '', 1, 100);

// Email
$var = admin_validate_email($_POST['email'] ?? '');

// Integer
$var = admin_validate_numeric($_POST['qty'] ?? 0, 'int', 0, 999);

// Float with range
$var = admin_validate_numeric($_POST['price'] ?? 0, 'float', 0, 9999.99);

// Enum (dari list)
$var = admin_validate_enum($_POST['status'] ?? '', ['Active', 'Inactive']);

// ID (must be > 0)
$var = admin_validate_id($_GET['id'] ?? 0);
```

---

## 🧪 TESTING CHECKLIST MINIMAL

Untuk setiap file yang di-fix:

- [ ] Buka halaman - tidak ada error
- [ ] Test search (jika ada) - tidak error, result correct
- [ ] Test create/edit - data saved correctly  
- [ ] Test delete (tanpa token) - should fail with CSRF error
- [ ] Test delete (dengan token) - should succeed
- [ ] Check file cleanup - old files removed

---

## 📞 HELPER FUNCTIONS READY TO USE

Semua functions di `admin_functions_secure.php` sudah siap digunakan:

```php
// Query
admin_query_execute($koneksi, $sql, $params, $types);
admin_query_fetch_one($koneksi, $sql, $params, $types);
admin_query_fetch_all($koneksi, $sql, $params, $types);

// Validation
admin_validate_string($value, $min, $max);
admin_validate_email($value);
admin_validate_numeric($value, $type, $min, $max);
admin_validate_enum($value, $allowed);

// File
admin_process_file_upload($file, $dir, $prefix, $allowed_ext, $max_size);
admin_delete_file($filepath);

// Security
admin_init_csrf_token();
admin_verify_csrf_token($token);
admin_csrf_token_field();

// Transaction
admin_execute_transaction($koneksi, function($k) { ... });

// Other
admin_pagination_calc($total, $per_page, $current_page);
admin_log_action($koneksi, $user_id, $action, $table, $record_id, $details);
```

---

## ⏱️ ESTIMATED TIME

- **Per file:** 10-15 menit
- **8 remaining files:** 1.5-2 hours total
- **Testing:** 30-45 menit

**Total time to complete all fixes:** ~2.5-3 hours

---

## 🎯 PRIORITY ORDER FOR FASTEST COMPLETION

1. **proses_hapus_penjual.php** (15 min) - Highest impact
2. **proses_hapus_user.php** (15 min)
3. **edit_penjual.php** (10 min) - Critical for password safety
4. **edit_user.php** (10 min)
5. **manajemen_user.php** (15 min) - Include pagination
6. **manajemen_penjual.php** (10 min)
7. **manajemen_menu.php** (10 min)
8. **tambah_penjual.php** (5 min)

Total: ~90 minutes

---

**Questions? Refer to:**
- `PERBAIKAN_MANUAL_CRUD.md` - Detailed examples
- `includes/admin_functions_secure.php` - Function definitions
- `COMPLETION_REPORT_AUDIT.md` - Complete status

