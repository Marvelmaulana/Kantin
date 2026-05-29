<?php
include __DIR__ . '/config/config.php';

echo "<h2>Kantin-Seller Linking Migration</h2>";

if (isset($_POST['run_migration'])) {
    echo "<h3>Running Migration...</h3>";
    
    // First, let's try to link by username pattern matching
    $update_count = 0;
    
    // Get all kantins without a penjual
    $query = "SELECT id_kantin, nama_kantin FROM kantin WHERE id_penjual IS NULL OR id_penjual = 0";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($kantin = mysqli_fetch_assoc($result)) {
            // Try to find matching seller by name
            // Extract seller name from kantin name (e.g., "PAK SAHUDI" from "Kantin Pak Sahudi")
            $kantin_name = strtoupper($kantin['nama_kantin']);
            
            // Remove common prefixes
            $search_name = str_replace(['KANTIN ', 'KEDAI ', 'WARUNG '], '', $kantin_name);
            $search_name = trim($search_name);
            
            echo "<p>Kantin: {$kantin['nama_kantin']} (ID: {$kantin['id_kantin']}) - Searching for: $search_name</p>";
            
            // Search for seller with matching name
            $seller_query = "SELECT id_user, username FROM users 
                           WHERE role = 'penjual' 
                           AND (username LIKE '%$search_name%' OR UPPER(username) = '$search_name')
                           LIMIT 1";
            
            $seller_result = mysqli_query($koneksi, $seller_query);
            
            if ($seller_result && mysqli_num_rows($seller_result) > 0) {
                $seller = mysqli_fetch_assoc($seller_result);
                
                // Update kantin with penjual
                $update_query = "UPDATE kantin SET id_penjual = {$seller['id_user']} WHERE id_kantin = {$kantin['id_kantin']}";
                
                if (mysqli_query($koneksi, $update_query)) {
                    echo "<span style='color: green;'>✅ Linked to seller: {$seller['username']} (ID: {$seller['id_user']})</span><br>";
                    $update_count++;
                } else {
                    echo "<span style='color: red;'>❌ Failed to update: " . mysqli_error($koneksi) . "</span><br>";
                }
            } else {
                echo "<span style='color: orange;'>⚠️ No matching seller found</span><br>";
            }
        }
    }
    
    echo "<h3>Migration Complete</h3>";
    echo "<p>Updated $update_count kantin records</p>";
    echo "<p><a href='check_kantin_setup.php'>Check Updated Kantin Status</a></p>";
    
} else {
    echo "<p>This script will try to link sellers to their kantins based on name matching.</p>";
    
    // Show preview
    echo "<h3>Preview - Kantins to be Updated:</h3>";
    
    $query = "SELECT id_kantin, nama_kantin FROM kantin WHERE id_penjual IS NULL OR id_penjual = 0 LIMIT 5";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<ul>";
        while ($kantin = mysqli_fetch_assoc($result)) {
            echo "<li>{$kantin['nama_kantin']}</li>";
        }
        echo "</ul>";
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='run_migration' value='1' style='padding: 10px 20px; background: #FF6B35; color: white; border: none; border-radius: 5px; cursor: pointer;'>Run Migration</button>";
        echo "</form>";
    }
}

?>
