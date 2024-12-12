<?php 
include '../db/config.php';

if (isset($_GET['story_id'])) {
    $story_id = intval($_GET['story_id']);

    $query = "SELECT cs.story_id, cs.title, cs.content, cs.picture, 
                     cu.first_name, cu.last_name,  cs.created_at
              FROM cancer_stories cs 
              JOIN cancer_patients cp ON cs.patient_id = cp.patient_id
              JOIN cancer_users cu ON cp.user_id = cu.user_id
              WHERE cs.story_id = ? AND cs.status = 'approved'";

    // Prepare statement with error checking
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $story_id);
    
    if (!$stmt->execute()) {
        die("Execute failed: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();

    if ($story = $result->fetch_assoc()) {
        ?>
        <div class="story-modal-content">
            <div class="story-header">
                <div class="author-profile">
                    <div class="profile-picture-container">
                        <img src="<?= htmlspecialchars(str_replace('../', '', $story['picture'])) ?? 'assets/images/default-story.jpg' ?>" 
                             alt="<?= htmlspecialchars($story['title']) ?>" 
                             class="profile-picture">
                    </div>
                    <div class="author-info">
                        <h2><?= htmlspecialchars($story['title']) ?></h2>
                        <div class="story-metadata">
                            <p>
                                <strong>Author:</strong> <?= htmlspecialchars($story['first_name'] . ' ' . $story['last_name']) ?><br>
                                <strong>Shared on:</strong> <?= date('F j, Y', strtotime($story['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="story-full-content">
                <?= $story['content'] ?>
            </div>
        </div>
        <?php
    } else {
        echo "<p>Story not found or no longer available.</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>Invalid story request.</p>";
}
?>

<style>
    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background-color: var(--white);
        border-radius: 10px;
        width: 80%;
        max-width: 700px;
        max-height: 80%;
        overflow-y: auto;
        padding: 30px;
        position: relative;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 24px;
        cursor: pointer;
        color: var(--peach);
    }

    /* New styles for the story header and profile picture */
    .story-header {
        margin-bottom: 30px;
    }

    .author-profile {
        display: flex;
        align-items: start;
        gap: 20px;
    }

    .profile-picture-container {
        flex-shrink: 0;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid var(--peach);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .profile-picture {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .author-info {
        flex-grow: 1;
    }

    .author-info h2 {
        margin-top: 0;
        color: var(--peach);
        margin-bottom: 10px;
    }

    .story-metadata {
        color: var(--gray-600);
    }

    .story-metadata p {
        margin: 0;
        line-height: 1.6;
    }

    .story-full-content {
        margin-top: 20px;
        line-height: 1.8;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .author-profile {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-picture-container {
            width: 100px;
            height: 100px;
        }
    }
</style>