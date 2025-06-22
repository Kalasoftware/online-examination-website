// ... existing code ...

// Get exam details including duration
$stmt = $db->prepare("SELECT * FROM exams WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header('Location: dashboard.php');
    exit();
}

<script>
function startTimer(duration) {
    let timer = duration * 60; // Convert minutes to seconds
    const timerDisplay = document.getElementById('timer');
    
    function updateTimer() {
        const minutes = Math.floor(timer / 60);
        const seconds = timer % 60;
        
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timer > 0) {
            timer--;
        } else {
            clearInterval(interval);
            document.getElementById('examForm').submit();
        }
    }
    
    updateTimer();
    const interval = setInterval(updateTimer, 1000);
}

// Start timer with actual exam duration from database
startTimer(<?php echo $exam['duration']; ?>);
</script>