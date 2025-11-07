<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get search query
$searchQuery = $_GET['q'] ?? '';
$searchType = $_GET['type'] ?? 'all';
$clubFilter = $_GET['club'] ?? '';

trim($searchQuery);
$results = [];
$totalResults = 0;

if (!empty($searchQuery)) {
    $searchTerm = "%$searchQuery%";
    
    // Search in Events
    if ($searchType === 'all' || $searchType === 'events') {
        $query = "
            SELECT e.*, c.name as club_name, c.acronym as club_acronym, c.color as club_color,
                   (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registered_count,
                   'event' as result_type
            FROM events e
            JOIN clubs c ON e.club_id = c.club_id
            WHERE (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ? OR e.event_type LIKE ?)
        ";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        if ($clubFilter) {
            $query .= " AND e.club_id = ?";
            $params[] = $clubFilter;
        }
        
        $query .= " ORDER BY e.event_date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $events);
    }
    
    // Search in Announcements
    if ($searchType === 'all' || $searchType === 'announcements') {
        $query = "
            SELECT a.*, c.name as club_name, c.acronym as club_acronym, c.color as club_color,
                   'announcement' as result_type
            FROM announcements a
            JOIN clubs c ON a.club_id = c.club_id
            WHERE (a.title LIKE ? OR a.content LIKE ?) AND a.status = 'Published'
        ";
        
        $params = [$searchTerm, $searchTerm];
        
        if ($clubFilter) {
            $query .= " AND a.club_id = ?";
            $params[] = $clubFilter;
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $announcements);
    }
    
    // Search in Users (for admin/president level)
    if (($searchType === 'all' || $searchType === 'users') && hasRole('President')) {
        $query = "
            SELECT u.user_id, u.username, u.full_name, u.email, u.role, u.status,
                   'user' as result_type
            FROM users u
            WHERE (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)
        ";
        
        $params = [$searchTerm, $searchTerm, $searchTerm];
        
        $query .= " ORDER BY u.full_name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $users);
    }
    
    // Search in Clubs
    if ($searchType === 'all' || $searchType === 'clubs') {
        $query = "
            SELECT c.*, 'club' as result_type
            FROM clubs c
            WHERE (c.name LIKE ? OR c.acronym LIKE ? OR c.description LIKE ? OR c.tagline LIKE ?)
        ";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        $query .= " ORDER BY c.name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_merge($results, $clubs);
    }
    
    $totalResults = count($results);
    
    // Sort all results by relevance (events by date, announcements by created_at, etc.)
    usort($results, function($a, $b) {
        if (isset($a['event_date']) && isset($b['event_date'])) {
            return strtotime($b['event_date']) - strtotime($a['event_date']);
        }
        if (isset($a['created_at']) && isset($b['created_at'])) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        }
        if (isset($a['achievement_date']) && isset($b['achievement_date'])) {
            return strtotime($b['achievement_date']) - strtotime($a['achievement_date']);
        }
        return 0;
    });
}

// Get all clubs for filter
$stmt = $conn->prepare("SELECT club_id, name, acronym FROM clubs ORDER BY name");
$stmt->execute();
$allClubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <!-- Hero Section -->
    <div class="relative h-64 bg-gradient-to-r from-purple-600 to-blue-600 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative z-10 container mx-auto px-6 h-full flex items-center">
            <div class="text-white">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Search Results</h1>
                <p class="text-xl opacity-90">Find events, announcements, and more across all clubs</p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8">
        <!-- Search Form -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 mb-8">
            <form method="GET" class="flex flex-col lg:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Query</label>
                    <input type="text" name="q" placeholder="Search for events, announcements, clubs..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="flex flex-wrap gap-2">
                    <select name="type" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $searchType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="events" <?php echo $searchType === 'events' ? 'selected' : ''; ?>>Events</option>
                        <option value="announcements" <?php echo $searchType === 'announcements' ? 'selected' : ''; ?>>Announcements</option>
                        <?php if (hasRole('President')): ?>
                            <option value="users" <?php echo $searchType === 'users' ? 'selected' : ''; ?>>Users</option>
                        <?php endif; ?>
                        <option value="clubs" <?php echo $searchType === 'clubs' ? 'selected' : ''; ?>>Clubs</option>
                    </select>
                    <select name="club" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Clubs</option>
                        <?php foreach ($allClubs as $club): ?>
                            <option value="<?php echo $club['club_id']; ?>" <?php echo $clubFilter == $club['club_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($club['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <?php if (!empty($searchQuery)): ?>
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">
                            Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"
                        </h2>
                        <p class="text-gray-600">
                            Found <?php echo $totalResults; ?> result<?php echo $totalResults !== 1 ? 's' : ''; ?>
                            <?php if ($searchType !== 'all'): ?>
                                in <?php echo ucfirst($searchType); ?>
                            <?php endif; ?>
                            <?php if ($clubFilter): ?>
                                <?php 
                                $clubName = array_filter($allClubs, function($club) use ($clubFilter) {
                                    return $club['club_id'] == $clubFilter;
                                });
                                $clubName = $clubName ? reset($clubName)['name'] : 'selected club';
                                ?>
                                for <?php echo htmlspecialchars($clubName); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($totalResults > 0): ?>
                        <div class="text-sm text-gray-500">
                            Sorted by relevance and date
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search Results -->
        <?php if (empty($searchQuery)): ?>
            <!-- Empty State - No Search -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-12 text-center">
                <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-blue-600 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Start Your Search</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Search for events, announcements, clubs, and members across the entire platform. 
                    Use the filters to narrow down your results.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <i class="fas fa-calendar-alt text-blue-600 text-2xl mb-2"></i>
                        <h4 class="font-semibold text-gray-800">Events</h4>
                        <p class="text-sm text-gray-600">Find upcoming workshops, seminars, and competitions</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <i class="fas fa-bullhorn text-green-600 text-2xl mb-2"></i>
                        <h4 class="font-semibold text-gray-800">Announcements</h4>
                        <p class="text-sm text-gray-600">Latest news and updates from clubs</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <i class="fas fa-users text-purple-600 text-2xl mb-2"></i>
                        <h4 class="font-semibold text-gray-800">Clubs</h4>
                        <p class="text-sm text-gray-600">Discover and join student organizations</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <i class="fas fa-user text-orange-600 text-2xl mb-2"></i>
                        <h4 class="font-semibold text-gray-800">Members</h4>
                        <p class="text-sm text-gray-600">Connect with other students (Admin only)</p>
                    </div>
                </div>
            </div>
        <?php elseif (empty($results)): ?>
            <!-- Empty State - No Results -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">No Results Found</h3>
                <p class="text-gray-600 mb-8">
                    We couldn't find any results for "<?php echo htmlspecialchars($searchQuery); ?>". 
                    Try adjusting your search terms or filters.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="search.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>New Search
                    </a>
                    <button onclick="history.back()" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Go Back
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Results Grid -->
            <div class="space-y-6">
                <?php foreach ($results as $result): ?>
                    <?php if ($result['result_type'] === 'event'): ?>
                        <!-- Event Result -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-<?php echo $result['club_color']; ?>-400 to-<?php echo $result['club_color']; ?>-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                                    <i class="fas fa-calendar-alt text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                                <a href="events.php?club=<?php echo $result['club_id']; ?>" class="hover:text-blue-600 transition-colors">
                                                    <?php echo htmlspecialchars($result['title']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex items-center space-x-3 text-sm text-gray-600 mb-2">
                                                <span class="px-2 py-1 bg-<?php echo $result['club_color']; ?>-100 text-<?php echo $result['club_color']; ?>-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['club_acronym']); ?>
                                                </span>
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['event_type']); ?>
                                                </span>
                                                <span class="text-gray-500">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo $result['registered_count']; ?>/<?php echo $result['max_attendees']; ?> registered
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm text-gray-500 flex-shrink-0">
                                            <div class="font-medium text-gray-700">
                                                <?php echo date('M j, Y', strtotime($result['event_date'])); ?>
                                            </div>
                                            <div><?php echo date('g:i A', strtotime($result['event_time'])); ?></div>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 line-clamp-2 mb-3">
                                        <?php echo htmlspecialchars(substr($result['description'], 0, 200)) . '...'; ?>
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars($result['location']); ?>
                                        </div>
                                        <a href="events.php" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                            View Event <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($result['result_type'] === 'announcement'): ?>
                        <!-- Announcement Result -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                                    <i class="fas fa-bullhorn text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                                <a href="announcements.php?club=<?php echo $result['club_id']; ?>" class="hover:text-blue-600 transition-colors">
                                                    <?php echo htmlspecialchars($result['title']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex items-center space-x-3 text-sm text-gray-600 mb-2">
                                                <span class="px-2 py-1 bg-<?php echo $result['club_color']; ?>-100 text-<?php echo $result['club_color']; ?>-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['club_acronym']); ?>
                                                </span>
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['type']); ?>
                                                </span>
                                                <span class="px-2 py-1 bg-<?php echo 
                                                    $result['priority'] === 'High' ? 'red' : 
                                                    ($result['priority'] === 'Medium' ? 'yellow' : 'gray')
                                                ?>-100 text-<?php echo 
                                                    $result['priority'] === 'High' ? 'red' : 
                                                    ($result['priority'] === 'Medium' ? 'yellow' : 'gray')
                                                ?>-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['priority']); ?> Priority
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm text-gray-500 flex-shrink-0">
                                            <?php echo date('M j, Y', strtotime($result['created_at'])); ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 line-clamp-3 mb-3">
                                        <?php echo nl2br(htmlspecialchars(substr($result['content'], 0, 250))) . '...'; ?>
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            Status: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($result['status']); ?></span>
                                        </div>
                                        <a href="announcements.php" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                            Read More <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($result['result_type'] === 'user' && hasRole('President')): ?>
                        <!-- User Result (Admin/President only) -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                                    <i class="fas fa-user text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                                <?php echo htmlspecialchars($result['full_name']); ?>
                                            </h3>
                                            <div class="flex items-center space-x-3 text-sm text-gray-600 mb-2">
                                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['role']); ?>
                                                </span>
                                                <span class="text-gray-500">
                                                    <?php echo htmlspecialchars($result['username']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm text-gray-500 flex-shrink-0">
                                            <span class="px-2 py-1 bg-<?php echo 
                                                $result['status'] === 'Active' ? 'green' : 'red'
                                            ?>-100 text-<?php echo 
                                                $result['status'] === 'Active' ? 'green' : 'red'
                                            ?>-800 rounded-full text-xs font-medium">
                                                <?php echo htmlspecialchars($result['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-gray-600 mb-3">
                                        <i class="fas fa-envelope mr-2"></i>
                                        <?php echo htmlspecialchars($result['email']); ?>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            User ID: #<?php echo $result['user_id']; ?>
                                        </div>
                                        <a href="user_profile.php?id=<?php echo $result['user_id']; ?>" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                                            View Profile <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($result['result_type'] === 'club'): ?>
                        <!-- Club Result -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl flex items-center justify-center text-white flex-shrink-0">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800 mb-1">
                                                <a href="clubs/<?php echo strtolower($result['acronym']); ?>.php" class="hover:text-blue-600 transition-colors">
                                                    <?php echo htmlspecialchars($result['name']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex items-center space-x-3 text-sm text-gray-600 mb-2">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                                                    <?php echo htmlspecialchars($result['acronym']); ?>
                                                </span>
                                                <span class="text-gray-500">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    Founded <?php echo date('Y', strtotime($result['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm text-gray-500 flex-shrink-0">
                                            <span class="px-2 py-1 bg-<?php echo 
                                                $result['status'] === 'Active' ? 'green' : 'red'
                                            ?>-100 text-<?php echo 
                                                $result['status'] === 'Active' ? 'green' : 'red'
                                            ?>-800 rounded-full text-xs font-medium">
                                                <?php echo htmlspecialchars($result['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 line-clamp-2 mb-3">
                                        <?php echo htmlspecialchars($result['description']); ?>
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-envelope mr-1"></i>
                                            <?php echo htmlspecialchars($result['email']); ?>
                                        </div>
                                        <a href="clubs/<?php echo strtolower($result['acronym']); ?>.php" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                            Visit Club <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Load More Button -->
            <?php if ($totalResults > 10): ?>
                <div class="text-center mt-8">
                    <button class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Load More Results
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-search on Enter key
document.querySelector('input[name="q"]').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});

// Highlight search terms in results
function highlightSearchTerms() {
    const searchQuery = '<?php echo addslashes($searchQuery); ?>';
    if (!searchQuery) return;
    
    const results = document.querySelectorAll('.bg-white\\/80');
    results.forEach(result => {
        const text = result.innerHTML;
        const highlighted = text.replace(new RegExp(searchQuery, 'gi'), match => 
            `<span class="bg-yellow-200 px-1 rounded">${match}</span>`
        );
        result.innerHTML = highlighted;
    });
}

// Initialize highlighting when page loads
document.addEventListener('DOMContentLoaded', function() {
    highlightSearchTerms();
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