<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Example</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body style="margin: 0; padding: 0; background: #f5f5f5; font-family: Arial, sans-serif;">
    <!-- Sidebar -->
    <nav id="sidebar" style="width: 250px; height: 100vh; background: rgba(255, 255, 255, 0.9); position: fixed; top: 0; left: 0; overflow-y: auto; transition: width 0.3s ease; z-index: 1000; box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);">
        <div style="padding: 10px;">
            <!-- Dashboard -->
            <a href="index.php?page=home" style="display: flex; align-items: center; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;">
                <span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-tachometer-alt"></i></span> Dashboard
            </a>

            <!-- Room Management Section -->
            <a href="#roomManagementCollapse" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                <span><span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-building"></i></span> Room Management</span> 
                <i class="fa fa-angle-down" style="font-size: 18px; color: #007bff;"></i>
            </a>
            <div id="roomManagementCollapse" style="display: none; padding-left: 20px;">
                <a href="index.php?page=floors" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-layer-group"></i></span> Floors
                </a>
                <a href="index.php?page=houses" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-home"></i></span> Rooms
                </a>
            </div>

            <!-- Tenants -->
            <a href="index.php?page=tenants" style="display: flex; align-items: center; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;">
                <span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-user-friends"></i></span> Tenants
            </a>

            <!-- Manage Payments Section -->
            <a href="#paymentsCollapse" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                <span><span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-file-invoice"></i></span> Manage Payments</span> 
                <i class="fa fa-angle-down" style="font-size: 18px; color: #007bff;"></i>
            </a>
            <div id="paymentsCollapse" style="display: none; padding-left: 20px;">
                <a href="index.php?page=payments" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-money-check-alt"></i></span> Payments
                </a>
                <a href="index.php?page=manage_defaulters" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-exclamation-triangle"></i></span> Manage Defaulters
                </a>
                <a href="index.php?page=manage_fines" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-gavel"></i></span> Manage Fines
                </a>
            </div>

            <!-- Expenses -->
            <a href="index.php?page=expenses" style="display: flex; align-items: center; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;">
                <span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-file-invoice-dollar"></i></span> Expenses
            </a>

            <!-- Reports Section -->
            <a href="#reportsCollapse" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                <span><span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-list-alt"></i></span> Reports</span> 
                <i class="fa fa-angle-down" style="font-size: 18px; color: #007bff;"></i>
            </a>
            <div id="reportsCollapse" style="display: none; padding-left: 20px;">
                <a href="index.php?page=payment_reports" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-chart-line"></i></span> Payment Reports
                </a>
                <a href="index.php?page=expense_reports" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-chart-bar"></i></span> Expense Reports
                </a>
                <a href="index.php?page=custom_reports" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-chart-pie"></i></span> Custom Reports
                </a>
            </div>

            <!-- Settings Section -->
            <a href="#settingsCollapse" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; color: #333; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 5px 0; transition: background 0.2s ease;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                <span><span style="margin-right: 10px; font-size: 18px; color: #007bff;"><i class="fa fa-cogs"></i></span> Settings</span> 
                <i class="fa fa-angle-down" style="font-size: 18px; color: #007bff;"></i>
            </a>
            <div id="settingsCollapse" style="display: none; padding-left: 20px;">
                <?php if (isset($_SESSION['login_type']) && $_SESSION['login_type'] == 1): ?>
                    <a href="index.php?page=users" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                        <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-users"></i></span> Users
                    </a>
                <?php endif; ?>
                <a href="index.php?page=billing_cycles" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-calendar-alt"></i></span> Billing Cycles
                </a>
                <a href="index.php?page=site_settings" style="display: flex; align-items: center; padding: 10px 15px; color: #555; text-decoration: none; font-size: 15px; border-radius: 5px; margin: 3px 0; transition: background 0.2s ease;">
                    <span style="margin-right: 10px; font-size: 16px; color: #007bff;"><i class="fa fa-cog"></i></span> System Settings
                </a>
            </div>
        </div>
    </nav>

    <!-- Media Query for Responsiveness -->
    <style>
        @media (max-width: 768px) {
            #sidebar {
                width: 200px;
            }
            #sidebar a {
                font-size: 14px;
                padding: 10px 12px;
            }
            #sidebar div div a {
                font-size: 13px;
            }
            div[style*="margin-left"] {
                margin-left: 200px;
            }
        }
        @media (max-width: 576px) {
            #sidebar {
                width: 60px;
                overflow-x: hidden;
            }
            #sidebar a span:not(.icon-field) {
                display: none;
            }
            #sidebar div div {
                padding-left: 0;
            }
            div[style*="margin-left"] {
                margin-left: 60px;
            }
        }
        #sidebar a:hover {
            background: rgba(0, 123, 255, 0.1);
        }
        #sidebar a.active {
            background: rgba(0, 123, 255, 0.3);
        }
    </style>
</body>
</html>