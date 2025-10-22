<?php
$code = isset($_POST['code']) ? $_POST['code'] : '';
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($code);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Designer Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #4f46e5;
            --background: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui;
            min-height: 100vh;
            background: var(--background);
            display: grid;
            place-items: center;
            padding: 1rem;
        }

        .container {
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-4px);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .title {
            font-size: 1.8rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .generate-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 0.75rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            transition: transform 0.2s ease;
        }

        .generate-btn:hover {
            transform: scale(0.98);
        }

        .generate-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 25%,
                rgba(255,255,255,0.2) 50%,
                transparent 75%
            );
            animation: shine 2s infinite;
        }

        .qr-result {
            margin-top: 2rem;
            text-align: center;
            opacity: 0;
            animation: fadeIn 0.4s forwards;
        }

        .qr-box {
            display: inline-block;
            padding: 1rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }

        .qr-box:hover {
            transform: rotate(2deg) scale(1.02);
        }

        @keyframes shine {
            from { transform: translateX(-50%) rotate(45deg); }
            to { transform: translateX(150%) rotate(45deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .powered-by {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">QR Designer Pro</h1>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <input 
                    type="text" 
                    class="input-field" 
                    name="code" 
                    placeholder="Entrez votre texte ou URL..." 
                    value="<?php echo htmlspecialchars($code); ?>"
                >
            </div>
            <button type="submit" class="generate-btn">
                <i class="fas fa-magic"></i> Générer QR Code
            </button>
        </form>

        <?php if (!empty($code)) : ?>
            <div class="qr-result">
                <div class="qr-box">
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
                </div>
                <p class="powered-by">Powered by QR Server API</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>