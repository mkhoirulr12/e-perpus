<?php
session_start();

include "../view/layout/header.php";
include "../view/layout/sidebar.php";
?>

<h4>Ubah Password</h4>

<form method="POST"
action="?controller=login&action=updatePassword">

<div class="mb-3">

<label>Password Lama</label>

<input type="password"
name="old_password"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Password Baru</label>

<input type="password"
name="new_password"
class="form-control"
required>

</div>

<button type="submit"
class="btn btn-primary">

Ubah Password

</button>

</form>

<?php
include "../view/layout/footer.php";
?>