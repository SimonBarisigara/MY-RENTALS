<style>
	.collapse a{
		text-indent:10px;
	}
	nav#sidebar{
		/*background: url(assets/uploads/<?php echo $_SESSION['system']['cover_img'] ?>) !important*/
	}
</style>

<nav id="sidebar" class='mx-lt-5 bg-dark' >
		
	<div class="sidebar-list">
		<a href="index.php?page=home" class="nav-item nav-home"><span class='icon-field'><i class="fa fa-tachometer-alt "></i></span> Dashboard</a>
		<a href="index.php?page=floors" class="nav-item nav-floors"><span class="icon-field"><i class="fa fa-building"></i></span> Floors</a>
		<a href="index.php?page=houses" class="nav-item nav-houses"><span class='icon-field'><i class="fa fa-home "></i></span> Rooms</a>
		<a href="index.php?page=tenants" class="nav-item nav-tenants"><span class='icon-field'><i class="fa fa-user-friends "></i></span> Tenants</a>
		<a href="index.php?page=payments" class="nav-item nav-invoices"><span class='icon-field'><i class="fa fa-file-invoice "></i></span> Payments</a>
		<a href="index.php?page=expenses" class="nav-item nav-expenses"><span class='icon-field'><i class="fa fa-file-invoice-dollar "></i></span> Expenses</a>

		<!-- Reports Section -->
		<a href="#reportsCollapse" class="nav-item nav-reports nav_collapse" data-toggle="collapse"><span class='icon-field'><i class="fa fa-list-alt "></i></span> Reports <i class="fa fa-angle-down"></i></a>
		<div id="reportsCollapse" class="collapse">
			<a href="index.php?page=monthly_reports" class="nav-item nav-monthly_reports"><span class='icon-field'><i class="fa fa-chart-line"></i></span> Monthly Reports</a>
			<a href="index.php?page=annual_reports" class="nav-item nav-annual_reports"><span class='icon-field'><i class="fa fa-chart-bar"></i></span> Annual Reports</a>
			<a href="index.php?page=custom_reports" class="nav-item nav-custom_reports"><span class='icon-field'><i class="fa fa-chart-pie"></i></span> Custom Reports</a>
		</div>

		<!-- Settings Section -->
		<a href="#settingsCollapse" class="nav-item nav-settings nav_collapse" data-toggle="collapse"><span class='icon-field'><i class="fa fa-cogs"></i></span> Settings <i class="fa fa-angle-down"></i></a>
		<div id="settingsCollapse" class="collapse">
		<?php if($_SESSION['login_type'] == 1): ?>
			<a href="index.php?page=users" class="nav-item nav-users"><span class='icon-field'><i class="fa fa-users "></i></span> Users</a>
		<?php endif; ?>			<a href="index.php?page=billing_cycles" class="nav-item nav-billing_cycles"><span class='icon-field'><i class="fa fa-calendar-alt"></i></span> Billing Cycles</a>
			<a href="index.php?page=site_settings" class="nav-item nav-site_settings"><span class='icon-field'><i class="fa fa-cog"></i></span> System Settings</a>
		</div>

		
	</div>

</nav>

<script>
	$('.nav_collapse').click(function(){
		console.log($(this).attr('href'))
		$($(this).attr('href')).collapse()
	})
	$('.nav-<?php echo isset($_GET['page']) ? $_GET['page'] : '' ?>').addClass('active')
</script>
