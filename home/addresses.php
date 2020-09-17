<?php
include("../php/db_connect.php");
session_start();
if (!isset($_SESSION['email'])) header('location: ../index.php');
$email = $_SESSION['email'];
$sqlAddresses = "SELECT * FROM cc_address WHERE email = ?";
$stmtAddress = $pdo -> prepare($sqlAddresses);
$stmtAddress -> execute([$email]);
?>
<div  class="col-12">
	<ul class="list-group list-group-flush">
		<li class="list-group-item mt-4 mb-4">
			<div class="row">
				<div class="col-xl-3 col-6">
					<h6>Διεύθυνση</h6>
				</div>
				<div class="col-xl-3 col-6">
					<h6>Περιοχή</h6>
				</div>
			</div>
		</li>
		<?php
		if ($stmtAddress -> rowCount() > 0){
			$i = 0;
			while ($row = $stmtAddress -> fetch()){
				$address = $row['address'];
				$state = $row['state'];
				?>
				<li class='list-group-item mt-2 mb-3'>
						<div class='row '>
							<div class='col-xl-3 col-6 align-middle'>
								<h6><?php echo $address; ?></h6>
							</div>
							<div class='col-xl-3 col-6 align-middle'>
								<h6><?php echo $state; ?></h6>
							</div>
							<div class='col-xl-2 col-12'>
								<button id="delete" class='btn btn-primary btn-block btn-danger' role='button'>Διαγραφή</button>
							</div>
						</div>
					</li>
				<?php
				$i += 1;
			}
		}
		else{
			?>
			<li class='list-group-item mt-2 mb-4'>
				<h6>Δεν υπάρχει ενεργή διεύθυνση</h6>
			</li>
			<?php
		}
		?>
	</ul>
	<script>
		<?php
		if ($stmtAddress -> rowCount() > 0) { ?>
			document.getElementById('delete').addEventListener('click', function removeAddress(e){
				e.preventDefault();
				loader.style.display = "block";
				blurred.style.display = "block";
				$('body').addClass('stop-scrolling');
				var xhr = new XMLHttpRequest();
				xhr.open('GET', 'address.php?address=<?php echo $address;?>', true);
				xhr.onreadystatechange = function(){
					if(this.status == 200){
						$("#home").load("address_menu.php");
						$("#addresses").load("addresses.php");
						$("#msg").html(this.responseText);
						loader.style.display = "none";
						blurred.style.display = "none";
						$('body').removeClass('stop-scrolling');
					}
					else{
						document.getElementById('false').innerHTML = this.responseText;
						$("#addresses").load("addresses.php");
						loader.style.display = "none";
						blurred.style.display = "none";
						$('body').removeClass('stop-scrolling');
					}
				}
				xhr.send();
			});
			<?php
		}
		else{
			?>
			<?php
		}
		?>
	</script>
</div>