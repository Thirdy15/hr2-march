<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomaly Detection with Machine Learning</title>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Performance Review</h1>
    <div>
        <canvas id="anomalyChart" width="800" height="400"></canvas>
    </div>
    <div id="output"></div>

    <script>
        // Fetch data from the backend
        async function fetchData() {
            const response = await fetch('/HR2/db/ml.php');
            const data = await response.json();
            return data;
        }

        // Normalize data
        function normalizeData(data) {
            const normalizedData = [];
            for (const [employee_id, records] of Object.entries(data)) {
                const { attendance, admin_evaluation, ptp_evaluation } = records;

                // Convert attendance status to numerical values
                const attendanceFeatures = [
                    attendance['present'] || 0,
                    attendance['absent'] || 0,
                    attendance['late'] || 0,
                    attendance['undertime'] || 0,
                    attendance['half-day'] || 0,
                    attendance['overtime'] || 0
                ];

                // Combine admin and PTP evaluation scores
                const performanceFeatures = [
                    admin_evaluation?.avg_quality || 0,
                    admin_evaluation?.avg_teamwork || 0,
                    admin_evaluation?.avg_communication || 0,
                    admin_evaluation?.avg_initiative || 0,
                    admin_evaluation?.avg_punctuality || 0,
                    ptp_evaluation?.avg_quality || 0,
                    ptp_evaluation?.avg_teamwork || 0,
                    ptp_evaluation?.avg_communication || 0,
                    ptp_evaluation?.avg_initiative || 0,
                    ptp_evaluation?.avg_punctuality || 0
                ];

                // Combine all features
                const features = [...attendanceFeatures, ...performanceFeatures];
                normalizedData.push({ employee_id, features });
            }

            return normalizedData;
        }

        // Build and train an autoencoder model
        async function trainModel(data) {
            const features = data.map(d => d.features);
            const tensorData = tf.tensor2d(features);

            // Normalize the data
            const min = tensorData.min();
            const max = tensorData.max();
            const normalizedData = tensorData.sub(min).div(max.sub(min));

            // Define the autoencoder model
            const model = tf.sequential();
            model.add(tf.layers.dense({ units: 8, activation: 'relu', inputShape: [features[0].length] }));
            model.add(tf.layers.dense({ units: 4, activation: 'relu' }));
            model.add(tf.layers.dense({ units: 8, activation: 'relu' }));
            model.add(tf.layers.dense({ units: features[0].length, activation: 'sigmoid' }));

            // Compile the model
            model.compile({ optimizer: 'adam', loss: 'meanSquaredError' });

            // Train the model
            await model.fit(normalizedData, normalizedData, {
                epochs: 50,
                batchSize: 32,
                shuffle: true
            });

            return { model, min, max };
        }

        // Detect anomalies using the trained model
        function detectAnomalies(data, model, min, max) {
            const anomalies = [];
            const threshold = 0.1; // Adjust this threshold as needed

            for (const { employee_id, features } of data) {
                const inputTensor = tf.tensor2d([features]);
                const normalizedInput = inputTensor.sub(min).div(max.sub(min));
                const outputTensor = model.predict(normalizedInput);
                const reconstructionError = tf.losses.meanSquaredError(normalizedInput, outputTensor).dataSync()[0];

                if (reconstructionError > threshold) {
                    anomalies.push({ employee_id, reconstructionError });
                }
            }

            return anomalies;
        }

        // Create a bar chart using Chart.js
        function createChart(employeeIds, reconstructionErrors, anomalies) {
            const ctx = document.getElementById('anomalyChart').getContext('2d');
            const anomalyIndices = new Set(anomalies.map(a => employeeIds.indexOf(a.employee_id)));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: employeeIds,
                    datasets: [{
                        label: 'Reconstruction Error',
                        data: reconstructionErrors,
                        backgroundColor: reconstructionErrors.map((error, index) =>
                            anomalyIndices.has(index) ? 'rgba(255, 99, 132, 0.6)' : 'rgba(54, 162, 235, 0.6)'
                        ),
                        borderColor: reconstructionErrors.map((error, index) =>
                            anomalyIndices.has(index) ? 'rgba(255, 99, 132, 1)' : 'rgba(54, 162, 235, 1)'
                        ),
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Reconstruction Error'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Employee ID'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const employeeId = employeeIds[context.dataIndex];
                                    const error = reconstructionErrors[context.dataIndex];
                                    return `Employee ID: ${employeeId}, Error: ${error.toFixed(4)}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Main function
        async function main() {
            const rawData = await fetchData();
            const normalizedData = normalizeData(rawData);
            const { model, min, max } = await trainModel(normalizedData);
            const anomalies = detectAnomalies(normalizedData, model, min, max);

            // Prepare data for the chart
            const employeeIds = normalizedData.map(d => d.employee_id);
            const reconstructionErrors = normalizedData.map(d => {
                const inputTensor = tf.tensor2d([d.features]);
                const normalizedInput = inputTensor.sub(min).div(max.sub(min));
                const outputTensor = model.predict(normalizedInput);
                return tf.losses.meanSquaredError(normalizedInput, outputTensor).dataSync()[0];
            });

            // Create the chart
            createChart(employeeIds, reconstructionErrors, anomalies);

            // Display anomalies
            const output = document.getElementById('output');
            anomalies.forEach(anomaly => {
                output.innerHTML += `
                    <p>
                        Employee ID: ${anomaly.employee_id}<br>
                        Reconstruction Error: ${anomaly.reconstructionError.toFixed(4)}<br>
                        <span style="color:red;">Anomaly Detected</span>
                    </p>
                    <hr>
                `;
            });
        }

        main();
    </script>
</body>
</html>