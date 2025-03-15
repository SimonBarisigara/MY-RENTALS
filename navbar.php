<style>
    .collapse a {
        text-indent: 10px;
    }
    nav#sidebar {
        /*background: url(assets/uploads/<?php echo $_SESSION['system']['cover_img'] ?>) !important*/
    }
    .nav-item.active > .collapse {
        display: block; /* Ensure submenu stays open when active */
    }
</style>

<nav id="sidebar" class='mx-lt-5 bg-dark'>
    <div class="sidebar-list">
        <!-- Dashboard -->
        <a href="index.php?page=home" class="nav-item nav-home">
            <span class='icon-field'><i class="fa fa-tachometer-alt"></i></span> Dashboard
        </a>

        <!-- Room Management Section -->
        <a href="#roomManagementCollapse" class="nav-item nav-room_management nav_collapse" data-bs-toggle="collapse">
            <span class='icon-field'><i class="fa fa-building"></i></span> Room Management <i class="fa fa-angle-down"></i>
        </a>
        <div id="roomManagementCollapse" class="collapse">
            <a href="index.php?page=floors" class="nav-item nav-floors">
                <span class='icon-field'><i class="fa fa-layer-group"></i></span> Floors
            </a>
            <a href="index.php?page=houses" class="nav-item nav-houses">
                <span class='icon-field'><i class="fa fa-home"></i></span> Rooms
            </a>
        </div>

        <!-- Tenants -->
        <a href="index.php?page=tenants" class="nav-item nav-tenants">
            <span class='icon-field'><i class="fa fa-user-friends"></i></span> Tenants
        </a>

        <!-- Payments -->
        <a href="index.php?page=payments" class="nav-item nav-payments">
            <span class='icon-field'><i class="fa fa-file-invoice"></i></span> Payments
        </a>

        <!-- Expenses -->
        <a href="index.php?page=expenses" class="nav-item nav-expenses">
            <span class='icon-field'><i class="fa fa-file-invoice-dollar"></i></span> Expenses
        </a>

        <!-- Reports Section -->
        <a href="#reportsCollapse" class="nav-item nav-reports nav_collapse" data-bs-toggle="collapse">
            <span class='icon-field'><i class="fa fa-list-alt"></i></span> Reports <i class="fa fa-angle-down"></i>
        </a>
        <div id="reportsCollapse" class="collapse">
            <a href="index.php?page=monthly_reports" class="nav-item nav-monthly_reports">
                <span class='icon-field'><i class="fa fa-chart-line"></i></span> Payment Reports
            </a>
            <a href="index.php?page=annual_reports" class="nav-item nav-annual_reports">
                <span class='icon-field'><i class="fa fa-chart-bar"></i></span> Expense Reports
            </a>
            <a href="index.php?page=custom_reports" class="nav-item nav-custom_reports">
                <span class='icon-field'><i class="fa fa-chart-pie"></i></span> Custom Reports
            </a>
        </div>

        <!-- Settings Section -->
        <a href="#settingsCollapse" class="nav-item nav-settings nav_collapse" data-bs-toggle="collapse">
            <span class='icon-field'><i class="fa fa-cogs"></i></span> Settings <i class="fa fa-angle-down"></i>
        </a>
        <div id="settingsCollapse" class="collapse">
            <?php if ($_SESSION['login_type'] == 1): ?>
                <a href="index.php?page=users" class="nav-item nav-users">
                    <span class='icon-field'><i class="fa fa-users"></i></span> Users
                </a>
            <?php endif; ?>
            <a href="index.php?page=billing_cycles" class="nav-item nav-billing_cycles">
                <span class='icon-field'><i class="fa fa-calendar-alt"></i></span> Billing Cycles
            </a>
            <a href="index.php?page=site_settings" class="nav-item nav-site_settings">
                <span class='icon-field'><i class="fa fa-cog"></i></span> System Settings
            </a>
        </div>
    </div>
</nav>

<script>
    // Add active class to the current page link and handle collapse
    document.addEventListener('DOMContentLoaded', () => {
        const currentPage = '<?php echo isset($_GET['page']) ? $_GET['page'] : ''; ?>';
        const navItems = document.querySelectorAll('.nav-item');

        navItems.forEach(item => {
            if (item.classList.contains(`nav-${currentPage}`)) {
                item.classList.add('active');
                // If it's a submenu item, ensure parent collapse is open
                const parentCollapse = item.closest('.collapse');
                if (parentCollapse) {
                    const parentNav = parentCollapse.previousElementSibling;
                    parentNav.classList.add('active');
                    parentCollapse.classList.add('show');
                }
            }
        });

        // Handle collapse toggle (Bootstrap 5 uses data-bs-toggle)
        document.querySelectorAll('.nav_collapse').forEach(collapseTrigger => {
            collapseTrigger.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default anchor behavior if needed
            });
        });
    });
</script>