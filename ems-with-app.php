<?php
/**
 * Plugin Name: Employee Management System
 * Description: Comprehensive employee management system with mobile app integration
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EMS_PLUGIN_VERSION', '1.0.0');

// Disable WordPress default filters that might interfere with REST API
remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
add_filter('rest_pre_serve_request', function($value) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
    header('Access-Control-Allow-Credentials: true');
    return $value;
});

class EmployeeManagementSystem {
    
    private $api;
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        // add_action('init', array($this, 'initialize_plugin'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
        add_action('wp_ajax_ems_ajax', array($this, 'handle_ajax_requests'));
        
        $this->api = new EMS_API();
    }
    
    public function activate() {
        $this->create_tables();
        $this->create_roles();
        $this->create_demo_user();
        $this->create_demo_data();
    }

    private function create_demo_user() {
        // Check if demo user exists
        $user = get_user_by('login', 'demo');
        error_log('Demo user check: ' . print_r($user, true));
        if (!$user) {
            // Create demo user
            $user_id = wp_create_user('demo', 'demo', 'demo@example.com');
            
            if (!is_wp_error($user_id)) {
                // Update user details
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => 'Demo Employee',
                    'first_name' => 'Demo',
                    'last_name' => 'Employee'
                ));
                
                // Add employee data
                global $wpdb;
                $wpdb->insert("{$wpdb->prefix}ems_employees", array(
                    'user_id' => $user_id,
                    'employee_id' => 'EMP001',
                    'department' => 'IT',
                    'position' => 'Software Developer',
                    'salary' => 5000.00,
                    'hire_date' => '2023-01-15',
                    'phone' => '+1234567890',
                    'status' => 'active'
                ));
            }
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            "ems_employees" => "
                CREATE TABLE {$wpdb->prefix}ems_employees (
                    id BIGINT(20) NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) NOT NULL,
                    employee_id VARCHAR(100) NOT NULL,
                    department VARCHAR(100),
                    position VARCHAR(100),
                    salary DECIMAL(10,2),
                    hire_date DATE,
                    phone VARCHAR(20),
                    address TEXT,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY employee_id (employee_id)
                ) $charset_collate;",
                
            "ems_attendance" => "
                CREATE TABLE {$wpdb->prefix}ems_attendance (
                    id BIGINT(20) NOT NULL AUTO_INCREMENT,
                    employee_id BIGINT(20) NOT NULL,
                    check_in DATETIME,
                    check_out DATETIME,
                    hours_worked DECIMAL(4,2),
                    date DATE,
                    location VARCHAR(255),
                    notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;",
                
            "ems_leaves" => "
                CREATE TABLE {$wpdb->prefix}ems_leaves (
                    id BIGINT(20) NOT NULL AUTO_INCREMENT,
                    employee_id BIGINT(20) NOT NULL,
                    leave_type ENUM('sick', 'vacation', 'personal', 'other'),
                    start_date DATE,
                    end_date DATE,
                    reason TEXT,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    approved_by BIGINT(20),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;",
                
            "ems_tasks" => "
                CREATE TABLE {$wpdb->prefix}ems_tasks (
                    id BIGINT(20) NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    assigned_to BIGINT(20),
                    assigned_by BIGINT(20),
                    due_date DATE,
                    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;",
                
            "ems_salary" => "
                CREATE TABLE {$wpdb->prefix}ems_salary (
                    id BIGINT(20) NOT NULL AUTO_INCREMENT,
                    employee_id BIGINT(20) NOT NULL,
                    month YEAR(4),
                    year YEAR(4),
                    basic_salary DECIMAL(10,2),
                    allowances DECIMAL(10,2),
                    deductions DECIMAL(10,2),
                    net_salary DECIMAL(10,2),
                    payment_date DATE,
                    status ENUM('pending', 'paid') DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_sql) {
            dbDelta($table_sql);
        }
    }
    
    private function create_roles() {
        add_role('ems_manager', 'EMS Manager', array(
            'read' => true,
            'manage_ems' => true,
        ));
        
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_ems');
        }
    }
    
    private function create_demo_data() {
        global $wpdb;
        
        // Create demo employees
        $demo_employees = array(
            array(
                'user_id' => 1,
                'employee_id' => 'EMP001',
                'department' => 'IT',
                'position' => 'Software Developer',
                'salary' => 5000.00,
                'hire_date' => '2023-01-15',
                'phone' => '+1234567890',
                'status' => 'active'
            ),
            array(
                'user_id' => 2,
                'employee_id' => 'EMP002',
                'department' => 'HR',
                'position' => 'HR Manager',
                'salary' => 6000.00,
                'hire_date' => '2023-02-20',
                'phone' => '+1234567891',
                'status' => 'active'
            )
        );
        
        foreach ($demo_employees as $employee) {
            $wpdb->insert("{$wpdb->prefix}ems_employees", $employee);
        }
    }
    
    public function register_admin_menu() {
        add_menu_page(
            'Employee Management',
            'EMS',
            'manage_ems',
            'ems-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        $submenu_pages = array(
            array('ems-dashboard', 'Dashboard', 'Dashboard', 'manage_ems', 'ems-dashboard', array($this, 'render_dashboard')),
            array('ems-dashboard', 'Employees', 'Employees', 'manage_ems', 'ems-employees', array($this, 'render_employees')),
            array('ems-dashboard', 'Attendance', 'Attendance', 'manage_ems', 'ems-attendance', array($this, 'render_attendance')),
            array('ems-dashboard', 'Leaves', 'Leaves', 'manage_ems', 'ems-leaves', array($this, 'render_leaves')),
            array('ems-dashboard', 'Tasks', 'Tasks', 'manage_ems', 'ems-tasks', array($this, 'render_tasks')),
            array('ems-dashboard', 'Payroll', 'Payroll', 'manage_ems', 'ems-payroll', array($this, 'render_payroll'))
        );
        
        foreach ($submenu_pages as $page) {
            add_submenu_page($page[0], $page[1], $page[2], $page[3], $page[4], $page[5]);
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ems-') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style('ems-admin-css', EMS_PLUGIN_URL . 'assets/css/admin.css', array(), EMS_PLUGIN_VERSION);
        
        // Enqueue JavaScript
        wp_enqueue_script('ems-admin-js', EMS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), EMS_PLUGIN_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ems-admin-js', 'ems_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ems_nonce')
        ));
        
        // Add inline CSS for admin panel
        $this->add_admin_styles();
    }
    
    private function add_admin_styles() {
        $css = "
        <style>
        .ems-admin {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg, #f8f9fa);
            color: var(--text-dark, #2c3e50);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        [data-theme='dark'] {
            --light-bg: #2c3e50;
            --dark-bg: #34495e;
            --text-light: #ecf0f1;
            --text-dark: #bdc3c7;
            --border-color: #4a5f7a;
        }
        
        .ems-header {
            background: var(--secondary-color, #2c3e50);
            color: var(--text-light, #ffffff);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-light, #ffffff);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        
        .ems-card {
            background: var(--text-light, #ffffff);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color, #3498db);
        }
        
        .ems-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .card-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
            color: var(--secondary-color, #2c3e50);
        }
        
        .ems-table-container {
            background: var(--text-light, #ffffff);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 2rem;
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color, #dee2e6);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color, #dee2e6);
            border-radius: 5px;
            width: 300px;
        }
        
        .ems-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .ems-table th, .ems-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color, #dee2e6);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--text-light, #ffffff);
            margin: 5% auto;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .dashboard-cards { grid-template-columns: 1fr; padding: 1rem; }
            .table-header { flex-direction: column; gap: 1rem; }
            .search-box { width: 100%; }
            .ems-table-container { margin: 1rem; overflow-x: auto; }
        }
        </style>
        ";
        
        echo $css;
    }
    
    public function register_rest_endpoints() {
        // Mobile authentication endpoints
        // register_rest_route('ems/v1', '/mobile/login', array(
        //     'methods' => 'POST',
        //     'callback' => array($this->api, 'mobile_login'),
        //     'permission_callback' => '__return_true'
        // ));
        register_rest_route('ems/v1', '/mobile/login', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'mobile_login'),
            'permission_callback' => '__return_true'
        ));
        
        // Attendance endpoints
        register_rest_route('ems/v1', '/mobile/attendance/checkin', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'mobile_checkin'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        register_rest_route('ems/v1', '/mobile/attendance/checkout', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'mobile_checkout'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        // Task endpoints
        register_rest_route('ems/v1', '/mobile/tasks', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_employee_tasks'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        register_rest_route('ems/v1', '/mobile/tasks/update', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'update_task_status'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        // Leave endpoints
        register_rest_route('ems/v1', '/mobile/leaves/apply', array(
            'methods' => 'POST',
            'callback' => array($this->api, 'apply_leave'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        register_rest_route('ems/v1', '/mobile/leaves/history', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_leave_history'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        // Salary endpoints
        register_rest_route('ems/v1', '/mobile/salary', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_salary_info'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
        
        // Profile endpoints
        register_rest_route('ems/v1', '/mobile/profile', array(
            'methods' => 'GET',
            'callback' => array($this->api, 'get_employee_profile'),
            'permission_callback' => array($this->api, 'verify_mobile_token')
        ));
    }
    
    public function handle_ajax_requests() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $action = $_POST['action'] ?? '';
        $method = $_POST['method'] ?? '';
        
        switch ($method) {
            case 'get_dashboard_stats':
                $this->get_dashboard_stats();
                break;
            case 'save_employee':
                $this->save_employee();
                break;
            case 'get_employees':
                $this->get_employees();
                break;
            case 'delete_employee':
                $this->delete_employee();
                break;
            default:
                wp_send_json_error('Invalid method');
        }
    }
    
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'total_employees' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ems_employees WHERE status = 'active'"),
            'pending_leaves' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ems_leaves WHERE status = 'pending'"),
            'today_tasks' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ems_tasks WHERE due_date = %s", date('Y-m-d'))),
            'payroll' => $wpdb->get_var("SELECT SUM(salary) FROM {$wpdb->prefix}ems_employees WHERE status = 'active'")
        );
        
        wp_send_json_success($stats);
    }
    
    // Render methods
    public function render_dashboard() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Employee Management System - Dashboard</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="dashboard-cards">
                <div class="ems-card total-employees">
                    <h3>Total Employees</h3>
                    <div class="card-value" data-stat="total_employees">0</div>
                    <div class="card-label">Active Staff Members</div>
                </div>
                
                <div class="ems-card pending-leaves">
                    <h3>Pending Leaves</h3>
                    <div class="card-value" data-stat="pending_leaves">0</div>
                    <div class="card-label">Awaiting Approval</div>
                </div>
                
                <div class="ems-card today-tasks">
                    <h3>Today\'s Tasks</h3>
                    <div class="card-value" data-stat="today_tasks">0</div>
                    <div class="card-label">Due Today</div>
                </div>
                
                <div class="ems-card payroll">
                    <h3>Monthly Payroll</h3>
                    <div class="card-value" data-stat="payroll">$0</div>
                    <div class="card-label">Total Salary</div>
                </div>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Recent Activities</h2>
                    <input type="text" class="search-box" placeholder="Search activities...">
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Activity</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>Checked in</td>
                            <td>' . date('Y-m-d H:i:s') . '</td>
                            <td><span class="btn btn-success">Present</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    public function render_employees() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Employee Management</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Employees</h2>
                    <div>
                        <input type="text" class="search-box" placeholder="Search employees...">
                        <button class="btn btn-primary btn-add" data-type="employee">Add Employee</button>
                    </div>
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employees-table-body">
                        <tr>
                            <td>EMP001</td>
                            <td>John Doe</td>
                            <td>IT</td>
                            <td>Software Developer</td>
                            <td>$5000</td>
                            <td><span class="btn btn-success">Active</span></td>
                            <td class="actions">
                                <button class="btn btn-warning">Edit</button>
                                <button class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    public function render_attendance() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Attendance Management</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Attendance Records</h2>
                    <input type="text" class="search-box" placeholder="Search attendance...">
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>' . date('Y-m-d') . '</td>
                            <td>09:00 AM</td>
                            <td>06:00 PM</td>
                            <td>9.0</td>
                            <td><span class="btn btn-success">Present</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    public function render_leaves() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Leave Management</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Leave Applications</h2>
                    <input type="text" class="search-box" placeholder="Search leaves...">
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>Vacation</td>
                            <td>2024-01-15</td>
                            <td>2024-01-20</td>
                            <td>Family vacation</td>
                            <td><span class="btn btn-warning">Pending</span></td>
                            <td class="actions">
                                <button class="btn btn-success">Approve</button>
                                <button class="btn btn-danger">Reject</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    public function render_tasks() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Task Management</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Tasks</h2>
                    <div>
                        <input type="text" class="search-box" placeholder="Search tasks...">
                        <button class="btn btn-primary btn-add" data-type="task">Add Task</button>
                    </div>
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Project Documentation</td>
                            <td>John Doe</td>
                            <td>2024-01-20</td>
                            <td><span class="btn btn-warning">Medium</span></td>
                            <td><span class="btn btn-danger">Pending</span></td>
                            <td class="actions">
                                <button class="btn btn-warning">Edit</button>
                                <button class="btn btn-danger">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
    
    public function render_payroll() {
        echo '
        <div class="ems-admin" data-theme="light">
            <div class="ems-header">
                <h1>Payroll Management</h1>
                <button class="theme-toggle">🌙</button>
            </div>
            
            <div class="ems-table-container">
                <div class="table-header">
                    <h2>Salary Records</h2>
                    <input type="text" class="search-box" placeholder="Search payroll...">
                </div>
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Month</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>January 2024</td>
                            <td>$5000</td>
                            <td>$500</td>
                            <td>$200</td>
                            <td>$5300</td>
                            <td><span class="btn btn-success">Paid</span></td>
                            <td class="actions">
                                <button class="btn btn-primary">Payslip</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
    }
}

class EMS_API {
    
    // public function mobile_login($request) {
    //     $params = $request->get_json_params();
    //     $username = sanitize_text_field($params['username']);
    //     $password = $params['password'];
        
    //     $user = wp_authenticate($username, $password);
        
    //     if (is_wp_error($user)) {
    //         return new WP_REST_Response(array(
    //             'success' => false,
    //             'message' => 'Invalid credentials'
    //         ), 401);
    //     }
        
    //     $token = $this->generate_token($user->ID);
    //     $employee_data = $this->get_employee_data($user->ID);
        
    //     return new WP_REST_Response(array(
    //         'success' => true,
    //         'token' => $token,
    //         'user' => $employee_data
    //     ));
    // }
    public function mobile_login($request) {
        $params = $request->get_json_params();
        
        // Add logging
        error_log('Login attempt - Username: ' . print_r($params, true));
        
        $username = sanitize_text_field($params['username'] ?? '');
        $password = $params['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Username and password are required'
            ), 400);
        }
        
        $user = wp_authenticate($username, $password);
        
        error_log('Authentication result: ' . print_r($user, true));
        
        if (is_wp_error($user)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $user->get_error_message()
            ), 401);
        }
        
        $token = $this->generate_token($user->ID);
        $employee_data = $this->get_employee_data($user->ID);
        
        error_log('Employee data: ' . print_r($employee_data, true));
        
        if (!$employee_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Employee record not found'
            ), 404);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'token' => $token,
            'user' => $employee_data
        ), 200);
    }
    
    public function mobile_checkin($request) {
        $params = $request->get_json_params();
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $data = array(
            'employee_id' => $employee_id,
            'check_in' => current_time('mysql'),
            'date' => current_time('Y-m-d'),
            'location' => sanitize_text_field($params['location']),
            'notes' => sanitize_text_field($params['notes'])
        );
        
        $wpdb->insert("{$wpdb->prefix}ems_attendance", $data);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Checked in successfully',
            'check_in_time' => $data['check_in']
        ));
    }
    
    public function mobile_checkout($request) {
        $params = $request->get_json_params();
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $check_out_time = current_time('mysql');
        
        // Calculate hours worked
        $attendance = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ems_attendance WHERE employee_id = %d AND date = %s ORDER BY id DESC LIMIT 1",
            $employee_id, current_time('Y-m-d')
        ));
        
        if ($attendance) {
            $check_in = strtotime($attendance->check_in);
            $check_out = strtotime($check_out_time);
            $hours_worked = round(($check_out - $check_in) / 3600, 2);
            
            $wpdb->update(
                "{$wpdb->prefix}ems_attendance",
                array(
                    'check_out' => $check_out_time,
                    'hours_worked' => $hours_worked
                ),
                array('id' => $attendance->id)
            );
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Checked out successfully',
                'hours_worked' => $hours_worked
            ));
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'No check-in record found'
        ), 400);
    }
    
    public function get_employee_tasks($request) {
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $tasks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ems_tasks WHERE assigned_to = %d ORDER BY due_date ASC",
            $employee_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'tasks' => $tasks
        ));
    }
    
    public function update_task_status($request) {
        $params = $request->get_json_params();
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $wpdb->update(
            "{$wpdb->prefix}ems_tasks",
            array('status' => sanitize_text_field($params['status'])),
            array(
                'id' => intval($params['task_id']),
                'assigned_to' => $employee_id
            )
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Task status updated'
        ));
    }
    
    public function apply_leave($request) {
        $params = $request->get_json_params();
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $data = array(
            'employee_id' => $employee_id,
            'leave_type' => sanitize_text_field($params['leave_type']),
            'start_date' => sanitize_text_field($params['start_date']),
            'end_date' => sanitize_text_field($params['end_date']),
            'reason' => sanitize_text_field($params['reason']),
            'status' => 'pending'
        );
        
        $wpdb->insert("{$wpdb->prefix}ems_leaves", $data);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Leave application submitted'
        ));
    }
    
    public function get_leave_history($request) {
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $leaves = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ems_leaves WHERE employee_id = %d ORDER BY created_at DESC",
            $employee_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'leaves' => $leaves
        ));
    }
    
    public function get_salary_info($request) {
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $salary = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ems_salary WHERE employee_id = %d ORDER BY year DESC, month DESC",
            $employee_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'salary' => $salary
        ));
    }
    
    public function get_employee_profile($request) {
        $employee_id = $this->get_employee_from_token($request);
        
        global $wpdb;
        
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name, u.user_email 
             FROM {$wpdb->prefix}ems_employees e 
             LEFT JOIN {$wpdb->prefix}users u ON e.user_id = u.ID 
             WHERE e.id = %d",
            $employee_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'profile' => $profile
        ));
    }
    
    public function verify_mobile_token($request) {
        $headers = $request->get_headers();
        $auth_header = $headers['authorization'][0] ?? '';
        
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
            return $this->validate_token($token);
        }
        
        return false;
    }
    
    private function generate_token($user_id) {
        return base64_encode($user_id . ':' . time() . ':' . wp_generate_password(32, false));
    }
    
    private function validate_token($token) {
        $parts = explode(':', base64_decode($token));
        if (count($parts) === 3) {
            $user_id = $parts[0];
            return get_user_by('id', $user_id) !== false;
        }
        return false;
    }
    
    private function get_employee_from_token($request) {
        $headers = $request->get_headers();
        $auth_header = $headers['authorization'][0] ?? '';
        $token = substr($auth_header, 7);
        $parts = explode(':', base64_decode($token));
        return intval($parts[0]);
    }
    
    private function get_employee_data($user_id) {
        global $wpdb;
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name as name, u.user_email as email 
            FROM {$wpdb->prefix}ems_employees e 
            LEFT JOIN {$wpdb->prefix}users u ON e.user_id = u.ID 
            WHERE e.user_id = %d",
            $user_id
        ));
        
        error_log('Employee query result: ' . print_r($employee, true));
        
        // If no employee record, create one
        if (!$employee) {
            $user = get_user_by('id', $user_id);
            
            // Create employee record
            $wpdb->insert("{$wpdb->prefix}ems_employees", array(
                'user_id' => $user_id,
                'employee_id' => 'EMP' . str_pad($user_id, 3, '0', STR_PAD_LEFT),
                'department' => 'General',
                'position' => 'Employee',
                'salary' => 0.00,
                'hire_date' => date('Y-m-d'),
                'phone' => '',
                'status' => 'active'
            ));
            
            // Fetch again
            $employee = $wpdb->get_row($wpdb->prepare(
                "SELECT e.*, u.display_name as name, u.user_email as email 
                FROM {$wpdb->prefix}ems_employees e 
                LEFT JOIN {$wpdb->prefix}users u ON e.user_id = u.ID 
                WHERE e.user_id = %d",
                $user_id
            ));
        }
        
        if (!$employee) {
            return null;
        }
        
        return array(
            'id' => $employee->id,
            'name' => $employee->name ?? 'Unknown',
            'email' => $employee->email ?? '',
            'employee_id' => $employee->employee_id ?? '',
            'department' => $employee->department ?? '',
            'position' => $employee->position ?? '',
            'phone' => $employee->phone ?? '',
            'hire_date' => $employee->hire_date ?? ''
        );
    }
}

new EmployeeManagementSystem();

// Add JavaScript directly
add_action('admin_footer', function() {
    ?>
    <script>
    class EMSAdmin {
        constructor() {
            this.currentTheme = localStorage.getItem('ems-theme') || 'light';
            this.init();
        }

        init() {
            this.applyTheme();
            this.bindEvents();
            this.loadDashboardStats();
        }

        applyTheme() {
            document.documentElement.setAttribute('data-theme', this.currentTheme);
            const themeToggle = document.querySelector('.theme-toggle');
            if (themeToggle) {
                themeToggle.innerHTML = this.currentTheme === 'dark' ? '☀️' : '🌙';
            }
        }

        bindEvents() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.theme-toggle')) {
                    this.toggleTheme();
                }
                
                if (e.target.closest('.modal-close') || e.target.classList.contains('modal')) {
                    this.closeModal();
                }
                
                if (e.target.closest('.btn-add')) {
                    this.openAddModal(e.target.dataset.type);
                }
            });

            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                searchBox.addEventListener('input', this.debounce(this.handleSearch, 300));
            }
        }

        toggleTheme() {
            this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            localStorage.setItem('ems-theme', this.currentTheme);
            this.applyTheme();
        }

        async loadDashboardStats() {
            try {
                const response = await this.apiCall('get_dashboard_stats');
                if (response.success) {
                    this.updateDashboardCards(response.data);
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        updateDashboardCards(stats) {
            const totalEmployees = document.querySelector('[data-stat="total_employees"]');
            const pendingLeaves = document.querySelector('[data-stat="pending_leaves"]');
            const todayTasks = document.querySelector('[data-stat="today_tasks"]');
            const payroll = document.querySelector('[data-stat="payroll"]');
            
            if (totalEmployees) totalEmployees.textContent = stats.total_employees;
            if (pendingLeaves) pendingLeaves.textContent = stats.pending_leaves;
            if (todayTasks) todayTasks.textContent = stats.today_tasks;
            if (payroll) payroll.textContent = '$' + stats.payroll;
        }

        openAddModal(type) {
            alert('Add ' + type + ' functionality would open here');
        }

        closeModal() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        handleSearch(event) {
            const searchTerm = event.target.value;
            console.log('Searching for:', searchTerm);
        }

        async apiCall(method, data = {}) {
            return new Promise((resolve, reject) => {
                jQuery.ajax({
                    url: ems_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ems_ajax',
                        method: method,
                        nonce: ems_ajax.nonce,
                        ...data
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response);
                        } else {
                            reject(response);
                        }
                    },
                    error: (xhr, status, error) => {
                        reject(error);
                    }
                });
            });
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        new EMSAdmin();
    });
    </script>
    <?php
});
?>