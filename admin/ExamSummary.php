<?php
function recalculateExamSummary($exam_id) {
    // 1. Futa muhtasari wa zamani kwa mtihani huu
    $conn = new mysqli('sql110.byetcluster.com', 'ezyro_41147622', 'your_password', 'ezyro_41147622_orientation');
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    // Futa records za zamani
    $conn->query("DELETE FROM exam_results_summary WHERE exam_id = $exam_id");

    // 2. Kokotoa upya kwa wanafunzi wote
    $sql = "
    INSERT INTO exam_results_summary (student_id, exam_id, total_marks, average_marks, grade, total_points, division)
    WITH
    all_marks AS (
        SELECT 
            student_id,
            SUM(marks) AS total_marks,
            AVG(marks) AS average_marks
        FROM marks
        WHERE exam_id = $exam_id
        GROUP BY student_id
    ),
    ranked_points AS (
        SELECT 
            student_id,
            marks,
            CASE 
                WHEN marks >= 75 THEN 1
                WHEN marks >= 65 THEN 2
                WHEN marks >= 45 THEN 3
                WHEN marks >= 30 THEN 4
                ELSE 5
            END AS points,
            ROW_NUMBER() OVER (
                PARTITION BY student_id 
                ORDER BY 
                    CASE 
                        WHEN marks >= 75 THEN 1
                        WHEN marks >= 65 THEN 2
                        WHEN marks >= 45 THEN 3
                        WHEN marks >= 30 THEN 4
                        ELSE 5
                    END ASC,
                    marks DESC
            ) AS rn
        FROM marks
        WHERE exam_id = $exam_id
    ),
    best7_points AS (
        SELECT 
            student_id,
            SUM(points) AS total_points
        FROM ranked_points
        WHERE rn <= 7
        GROUP BY student_id
    )
    SELECT 
        a.student_id,
        a.total_marks,
        a.average_marks,
        CASE 
            WHEN a.average_marks >= 75 THEN 'A'
            WHEN a.average_marks >= 65 THEN 'B'
            WHEN a.average_marks >= 45 THEN 'C'
            WHEN a.average_marks >= 30 THEN 'D'
            ELSE 'F'
        END,
        b.total_points,
        CASE 
            WHEN b.total_points BETWEEN 7 AND 17 THEN 'I'
            WHEN b.total_points BETWEEN 18 AND 22 THEN 'II'
            WHEN b.total_points BETWEEN 23 AND 25 THEN 'III'
            WHEN b.total_points BETWEEN 26 AND 33 THEN 'IV'
            ELSE '0'
        END
    FROM all_marks a
    LEFT JOIN best7_points b ON a.student_id = b.student_id";

    if (!$conn->query($sql)) {
        echo "Error inserting summary: " . $conn->error;
    }

    // 3. Kokotoa nafasi (position)
    $conn->query("SET @rank = 0");
    $conn->query("
        UPDATE exam_results_summary 
        SET position = (@rank := @rank + 1)
        WHERE exam_id = $exam_id
        ORDER BY average_marks DESC
    ");

    $conn->close();
}
?>