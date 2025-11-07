<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle event registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: events.php');
        exit();
    }
    
    $event_id = (int)$_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if already registered
        $stmt = $conn->prepare("SELECT * FROM registrations WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            // Register for event
            $stmt = $conn->prepare("INSERT INTO registrations (event_id, user_id, registration_date) VALUES (?, ?, NOW())");
            $stmt->execute([$event_id, $user_id]);
            
            // Update event registered count
            $stmt = $conn->prepare("UPDATE events SET registered_count = registered_count + 1 WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            $_SESSION['success'] = 'Successfully registered for the event!';
        } else {
            $_SESSION['error'] = 'You are already registered for this event';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Registration failed. Please try again.';
    }
    
    header('Location: events.php');
    exit();
}

// Handle event cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: events.php');
        exit();
    }
    
    $event_id = (int)$_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Cancel registration
        $stmt = $conn->prepare("DELETE FROM registrations WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Update event registered count
            $stmt = $conn->prepare("UPDATE events SET registered_count = registered_count - 1 WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            $_SESSION['success'] = 'Registration cancelled successfully!';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Cancellation failed. Please try again.';
    }
    
    header('Location: events.php');
    exit();
}

// Handle event creation (Admin/President only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    if (!hasRole('President') && !hasRole('Admin') && !hasRole('SuperAdmin')) {
        $_SESSION['error'] = 'You do not have permission to create events';
        header('Location: events.php');
        exit();
    }
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: events.php');
        exit();
    }
    
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = sanitizeInput($_POST['location']);
    $max_attendees = (int)$_POST['max_attendees'];
    $club_id = (int)$_POST['club_id'];
    $event_type = $_POST['event_type'];
    $registration_deadline = $_POST['registration_deadline'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO events (club_id, title, description, event_date, event_time, location, max_attendees, event_type, registration_deadline, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$club_id, $title, $description, $event_date, $event_time, $location, $max_attendees, $event_type, $registration_deadline, $_SESSION['user_id']]);
        
        $_SESSION['success'] = 'Event created successfully!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Event creation failed. Please try again.';
    }
    
    header('Location: events.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$club_filter = $_GET['club'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT e.*, c.name as club_name, c.color as club_color,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registered_count,
           CASE 
               WHEN e.event_date < CURDATE() THEN 'Past'
               WHEN e.event_date = CURDATE() THEN 'Today'
               WHEN e.registration_deadline < CURDATE() THEN 'Registration Closed'
               ELSE 'Upcoming'
           END as status
    FROM events e
    JOIN clubs c ON e.club_id = c.club_id
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($club_filter) {
    $query .= " AND e.club_id = ?";
    $params[] = $club_filter;
}

if ($type_filter) {
    $query .= " AND e.event_type = ?";
    $params[] = $type_filter;
}

if ($status_filter) {
    if ($status_filter === 'Upcoming') {
        $query .= " AND e.event_date >= CURDATE() AND e.registration_deadline >= CURDATE()";
    } elseif ($status_filter === 'Past') {
        $query .= " AND e.event_date < CURDATE()";
    } elseif ($status_filter === 'Today') {
        $query .= " AND e.event_date = CURDATE()";
    } elseif ($status_filter === 'Registration Closed') {
        $query .= " AND e.registration_deadline < CURDATE() AND e.event_date > CURDATE()";
    }
}

$query .= " ORDER BY e.event_date ASC, e.event_time ASC";

// Get events
$stmt = $conn->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's registered events
$user_registered_events = [];
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT event_id FROM registrations WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_registered_events = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'event_id');
}

// Get clubs for filter and creation
$stmt = $conn->prepare("SELECT * FROM clubs ORDER BY name");
$stmt->execute();
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <!-- Hero Section -->
    <div class="relative h-64 bg-gradient-to-r from-blue-600 to-purple-600 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative z-10 container mx-auto px-6 h-full flex items-center">
            <div class="text-white">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Club Events</h1>
                <p class="text-xl opacity-90">Discover exciting events across all clubs</p>
            </div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8">
        <!-- Filters and Search -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 mb-8">
            <div class="flex flex-col lg:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Events</label>
                    <input type="text" id="searchInput" placeholder="Search by title, description, or location..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="flex flex-wrap gap-2">
                    <select id="clubFilter" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Clubs</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo $club['club_id']; ?>" <?php echo $club_filter == $club['club_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($club['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="typeFilter" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="Workshop" <?php echo $type_filter == 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                        <option value="Seminar" <?php echo $type_filter == 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                        <option value="Competition" <?php echo $type_filter == 'Competition' ? 'selected' : ''; ?>>Competition</option>
                        <option value="Social" <?php echo $type_filter == 'Social' ? 'selected' : ''; ?>>Social</option>
                        <option value="Meeting" <?php echo $type_filter == 'Meeting' ? 'selected' : ''; ?>>Meeting</option>
                    </select>
                    <select id="statusFilter" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="Upcoming" <?php echo $status_filter == 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Today" <?php echo $status_filter == 'Today' ? 'selected' : ''; ?>>Today</option>
                        <option value="Registration Closed" <?php echo $status_filter == 'Registration Closed' ? 'selected' : ''; ?>>Registration Closed</option>
                        <option value="Past" <?php echo $status_filter == 'Past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                    <button onclick="applyFilters()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Apply Filters
                    </button>
                    <?php if (hasRole('President') || hasRole('Admin') || hasRole('SuperAdmin')): ?>
                        <button onclick="openCreateModal()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Create Event
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="eventsGrid">
            <?php if (empty($events)): ?>
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📅</div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No events found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <!-- Event Header -->
                        <div class="relative">
                            <div class="h-48 bg-gradient-to-br from-<?php echo $event['club_color']; ?>-400 to-<?php echo $event['club_color']; ?>-600 relative overflow-hidden">
                                <div class="absolute inset-0 bg-black/20"></div>
                                <div class="relative z-10 p-6 h-full flex flex-col justify-between text-white">
                                    <div class="flex justify-between items-start">
                                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium backdrop-blur-sm">
                                            <?php echo htmlspecialchars($event['event_type']); ?>
                                        </span>
                                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-medium backdrop-blur-sm">
                                            <?php echo htmlspecialchars($event['status']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($event['club_name']); ?></h3>
                                        <p class="text-sm opacity-90"><?php echo date('M j, Y', strtotime($event['event_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="p-6">
                            <h4 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </h4>
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </p>

                            <!-- Event Info -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-calendar-alt w-4 mr-2"></i>
                                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?> at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-users w-4 mr-2"></i>
                                    <?php echo $event['registered_count']; ?> / <?php echo $event['max_attendees']; ?> registered
                                </div>
                                <?php if ($event['registration_deadline']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-clock w-4 mr-2"></i>
                                        Register by: <?php echo date('M j, Y', strtotime($event['registration_deadline'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Registration Progress</span>
                                    <span><?php echo round(($event['registered_count'] / $event['max_attendees']) * 100); ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-300" 
                                         style="width: <?php echo min(100, ($event['registered_count'] / $event['max_attendees']) * 100); ?>%">
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <button onclick="viewEventDetails(<?php echo $event['event_id']; ?>)" 
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                    View Details
                                </button>
                                <?php if ($event['status'] === 'Upcoming'): ?>
                                    <?php if (in_array($event['event_id'], $user_registered_events)): ?>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <button type="submit" name="cancel_registration" 
                                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium"
                                                    onclick="return confirm('Are you sure you want to cancel your registration?')">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <button type="submit" name="register_event" 
                                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                                                    <?php echo $event['registered_count'] >= $event['max_attendees'] ? 'disabled' : ''; ?>>
                                                <?php echo $event['registered_count'] >= $event['max_attendees'] ? 'Full' : 'Register'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php elseif ($event['status'] === 'Past'): ?>
                                    <button class="flex-1 px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed text-sm font-medium">
                                        Completed
                                    </button>
                                <?php else: ?>
                                    <button class="flex-1 px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed text-sm font-medium">
                                        Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div id="createModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">Create New Event</h2>
                    <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="create_event" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event Title *</label>
                        <input type="text" name="title" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event Type *</label>
                        <select name="event_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Competition">Competition</option>
                            <option value="Social">Social</option>
                            <option value="Meeting">Meeting</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" required rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Describe your event..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event Date *</label>
                        <input type="date" name="event_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Event Time *</label>
                        <input type="time" name="event_time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                        <input type="text" name="location" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Event venue">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Attendees *</label>
                        <input type="number" name="max_attendees" required min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Club *</label>
                        <select name="club_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Club</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?php echo $club['club_id']; ?>">
                                    <?php echo htmlspecialchars($club['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Registration Deadline *</label>
                        <input type="date" name="registration_deadline" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        Create Event
                    </button>
                    <button type="button" onclick="closeCreateModal()" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter functionality
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const club = document.getElementById('clubFilter').value;
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = 'events.php?';
    const params = [];
    
    if (search) params.push('search=' + encodeURIComponent(search));
    if (club) params.push('club=' + club);
    if (type) params.push('type=' + encodeURIComponent(type));
    if (status) params.push('status=' + encodeURIComponent(status));
    
    url += params.join('&');
    window.location.href = url;
}

// Modal functions
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function viewEventDetails(eventId) {
    alert('Event details functionality will be implemented soon!');
}

// Search on Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Close modal on outside click
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});

// Set minimum dates
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const eventDateInputs = document.querySelectorAll('input[name="event_date"]');
    const regDeadlineInputs = document.querySelectorAll('input[name="registration_deadline"]');
    
    eventDateInputs.forEach(input => input.min = today);
    regDeadlineInputs.forEach(input => input.min = today);
});
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include 'includes/footer.php'; ?>