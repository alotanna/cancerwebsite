<?php 
include '../db/config.php';

if (isset($_GET['resource_id'])) {
    $resource_id = intval($_GET['resource_id']);

    $query = "SELECT cr.resource_id, cr.title, cr.content, cr.picture, cr.resource_type,
                     cu.first_name, cu.last_name, ct.cancer_type_name, cr.created_at
              FROM cancer_resources cr 
              JOIN cancer_users cu ON cr.user_id = cu.user_id
              LEFT JOIN cancer_types ct ON cr.cancer_type_id = ct.cancer_type_id
              WHERE cr.resource_id = ? AND cr.status = 'approved'";

    // Prepare statement with error checking
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $resource_id);
    
    if (!$stmt->execute()) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();

    if ($resource = $result->fetch_assoc()) {
        ?>
        <div class="resource-modal-content">
            <div class="resource-header">
                <div class="resource-info">
                    <div class="resource-image">
                        <img src="<?= htmlspecialchars(str_replace('../', '', $resource['picture'])) ?? 'assets/images/default-resource.jpg' ?>" 
                             alt="<?= htmlspecialchars($resource['title']) ?>" 
                             class="resource-main-image">
                    </div>
                    <div class="resource-metadata">
                        <h2><?= htmlspecialchars($resource['title']) ?></h2>
                        <p>
                            <strong>Author:</strong> <?= htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']) ?><br>
                            <strong>Category:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucfirst($resource['resource_type']))) ?><br>
                            <strong>Cancer Type:</strong> <?= htmlspecialchars($resource['cancer_type_name'] ?? 'All Types') ?><br>
                            <strong>Published:</strong> <?= date('F j, Y', strtotime($resource['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="resource-full-content">
                <?= $resource['content'] ?>
            </div>
        </div>
        <?php
    } else {
        echo "<p>Resource not found or no longer available.</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>Invalid resource request.</p>";
}
?>

<style>
    .resource-modal-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .resource-header {
        margin-bottom: 30px;
    }

    .resource-info {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .resource-image {
        width: 100%;
        max-height: 400px;
        overflow: hidden;
        border-radius: 10px;
    }

    .resource-main-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .resource-metadata {
        flex-grow: 1;
    }

    .resource-metadata h2 {
        color: var(--peach);
        margin-bottom: 15px;
    }

    .resource-metadata p {
        line-height: 1.6;
        margin: 0;
    }

    .resource-full-content {
        line-height: 1.8;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .resource-info {
            flex-direction: column;
        }

        .resource-image {
            max-height: 300px;
        }
    }
</style>