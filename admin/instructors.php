<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Delete instructor if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM instructors WHERE id = ?");
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Redirect to avoid resubmission
    header('Location: instructors.php?deleted=1');
    exit;
}

// Get all instructors
$stmt = $conn->prepare("SELECT i.*, COUNT(c.id) as course_count 
                       FROM instructors i 
                       LEFT JOIN courses c ON i.id = c.instructor_id 
                       GROUP BY i.id 
                       ORDER BY i.name");
$stmt->execute();
$instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Instructors";
include 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Instructors</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add-instructor.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i> Add New Instructor
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Instructor deleted successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Email</th>
                            <th>Courses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($instructors) > 0): ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <td><?php echo $instructor['id']; ?></td>
                                    <td>
                                        <?php if (!empty($instructor['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($instructor['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($instructor['name']); ?>" 
                                                 width="50" height="50" class="rounded-circle">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <?php echo strtoupper(substr($instructor['name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['title'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                    <td><?php echo $instructor['course_count']; ?></td>
                                    <td>
                                        <a href="edit-instructor.php?id=<?php echo $instructor['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view-instructor.php?id=<?php echo $instructor['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-danger" 
                                           onclick="confirmDelete(<?php echo $instructor['id']; ?>, '<?php echo htmlspecialchars($instructor['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No instructors found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete instructor "' + name + '"?')) {
        window.location.href = 'instructors.php?delete=' + id;
    }
}
</script>

