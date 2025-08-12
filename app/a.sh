# sesuaikan daftar file .php kamu
for f in dashboard.php domains.php domain_expiry_notify.php name.php list_vm.php settings.php users.php logs.php; do
  [ -f "$f" ] || continue
  # cek ada menu NAME dan belum ada VM Biznet
  if grep -q '<a href="name.php"' "$f" && ! grep -q 'list_vm.php' "$f"; then
    sed -i '/<a href="name\.php"[^>]*>.*NAME<\/a>/a\  <a href="list_vm.php"><i class="bi bi-hdd-network me-2"></i>VM Biznet</a>' "$f"
    echo "Updated: $f"
  fi
done
