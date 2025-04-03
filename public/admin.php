<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database connection
$dbPath = getenv('DATABASE_PATH') ?: __DIR__ . '/../database/tweets.db';
$db = new SQLite3($dbPath);

// Ensure database directory exists
$dbDir = dirname($dbPath);
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $content = $_POST['content'] ?? '';
            $created_at = $_POST['created_at'] ?? date('Y-m-d H:i:s');
            
            if (!empty($content)) {
                $stmt = $db->prepare('INSERT INTO tweets (content, created_at) VALUES (:content, :created_at)');
                $stmt->bindValue(':content', $content, SQLITE3_TEXT);
                $stmt->bindValue(':created_at', $created_at, SQLITE3_TEXT);
                $stmt->execute();
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $content = $_POST['content'] ?? '';
            $created_at = $_POST['created_at'] ?? '';
            
            if (!empty($id) && !empty($content)) {
                $stmt = $db->prepare('UPDATE tweets SET content = :content, created_at = :created_at WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->bindValue(':content', $content, SQLITE3_TEXT);
                $stmt->bindValue(':created_at', $created_at, SQLITE3_TEXT);
                $stmt->execute();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            
            if (!empty($id)) {
                $stmt = $db->prepare('DELETE FROM tweets WHERE id = :id');
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->execute();
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch tweet for editing if ID is provided
$editTweet = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM tweets WHERE id = :id');
    $stmt->bindValue(':id', $_GET['edit'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $editTweet = $result->fetchArray(SQLITE3_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Friend of a Global Economy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900" x-data="{ showDeleteConfirm: false, deleteId: null }">
    <div class="min-h-full">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Panel</h1>
                    <a href="/" class="text-blue-600 dark:text-blue-400 hover:underline">View Website</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Add/Edit Tweet Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                    <?php echo $editTweet ? 'Edit Tweet' : 'Add New Tweet'; ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $editTweet ? 'update' : 'create'; ?>">
                    <?php if ($editTweet): ?>
                        <input type="hidden" name="id" value="<?php echo $editTweet['id']; ?>">
                    <?php endif; ?>
                    
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tweet Content</label>
                        <textarea 
                            id="content" 
                            name="content" 
                            rows="4" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            required
                        ><?php echo $editTweet ? htmlspecialchars($editTweet['content']) : ''; ?></textarea>
                    </div>
                    <div>
                        <label for="created_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date & Time</label>
                        <input 
                            type="datetime-local" 
                            id="created_at" 
                            name="created_at" 
                            value="<?php echo $editTweet ? date('Y-m-d\TH:i', strtotime($editTweet['created_at'])) : date('Y-m-d\TH:i'); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                    <div class="flex space-x-4">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <?php echo $editTweet ? 'Update Tweet' : 'Add Tweet'; ?>
                        </button>
                        <?php if ($editTweet): ?>
                            <a href="admin.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Existing Tweets -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Existing Tweets</h2>
                <?php
                $results = $db->query('SELECT * FROM tweets ORDER BY created_at DESC');
                while ($row = $results->fetchArray(SQLITE3_ASSOC)):
                ?>
                    <div class="border-b border-gray-200 dark:border-gray-700 py-4">
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-gray-600 dark:text-gray-300 text-sm">
                                <?php echo date('F j, Y, g:i a', strtotime($row['created_at'])); ?>
                            </p>
                            <div class="flex space-x-2">
                                <a href="?edit=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button 
                                    @click="showDeleteConfirm = true; deleteId = <?php echo $row['id']; ?>"
                                    class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-gray-900 dark:text-white whitespace-pre-line">
                            <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                        </p>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div 
        x-show="showDeleteConfirm" 
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4"
        x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Confirm Delete</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to delete this tweet? This action cannot be undone.</p>
            <div class="flex justify-end space-x-4">
                <button 
                    @click="showDeleteConfirm = false" 
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Cancel
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" x-bind:value="deleteId">
                    <button 
                        type="submit"
                        class="px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html> 