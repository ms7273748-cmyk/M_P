<?php
session_start();
require_once 'header.php'; // optional include
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Clubs | ClubSphere</title>

  <style>
    /* --- Container Setup --- */
    .hero-visual {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      padding: 60px 20px;
      background: transparent;
      text-align: center;
    }

    .hero-visual h1 {
      color: #00ffe7;
      font-size: 2.4rem;
      letter-spacing: 3px;
      text-transform: uppercase;
      margin-bottom: 40px;
      text-shadow: 0 0 15px #00ffe7, 0 0 40px #007bff;
      animation: fadeInTitle 1.2s ease forwards;
    }

    .text-box-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 28px;
      max-width: 900px;
      animation: fadeIn 1.5s ease forwards;
    }

    /* --- Box Style --- */
    .text-box {
      position: relative;
      padding: 20px 50px;
      background: rgba(0, 255, 230, 0.05);
      border-radius: 20px;
      overflow: hidden;
      cursor: pointer;
      text-align: center;
      transition: transform 0.4s ease, background 0.4s ease;
      box-shadow: 0 0 10px rgba(0, 255, 230, 0.2);
      min-width: 200px;
      text-decoration: none;
      display: inline-block;
    }

    .text-box span {
      position: relative;
      z-index: 2;
      color: #00ffe7;
      font-weight: 700;
      font-size: 1.3rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      text-shadow: 0 0 8px #00ffe7, 0 0 15px #00b3ff;
    }

    /* --- Animated Border --- */
    .text-box::before {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: 20px;
      padding: 2px;
      background: linear-gradient(90deg, #00fff2, #007bff, #00fff2, #00fff2);
      background-size: 300% 300%;
      animation: borderSweep 4s linear infinite;
      -webkit-mask: 
        linear-gradient(#fff 0 0) content-box, 
        linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      z-index: 1;
    }

    /* --- Hover Effects --- */
    .text-box:hover {
      transform: scale(1.08);
      background: rgba(0, 255, 230, 0.12);
      box-shadow: 0 0 20px rgba(0, 255, 230, 0.5);
    }

    /* --- Entry Animation --- */
    .text-box {
      opacity: 0;
      transform: translateY(20px);
      animation: slideIn 0.8s ease forwards;
    }
    .text-box:nth-child(1) { animation-delay: 0.2s; }
    .text-box:nth-child(2) { animation-delay: 0.4s; }
    .text-box:nth-child(3) { animation-delay: 0.6s; }
    .text-box:nth-child(4) { animation-delay: 0.8s; }
    .text-box:nth-child(5) { animation-delay: 1s; }
    .text-box:nth-child(6) { animation-delay: 1.2s; }

    /* --- Keyframes --- */
    @keyframes borderSweep {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateY(30px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes fadeInTitle {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* --- Responsive --- */
    @media (max-width: 768px) {
      .text-box {
        min-width: 150px;
        padding: 16px 30px;
      }
      .text-box span {
        font-size: 1.1rem;
      }
      .hero-visual h1 {
        font-size: 2rem;
      }
    }
  </style>
</head>

<body>
  <section class="hero-visual">
    <h1>Our Clubs</h1>

    <div class="text-box-container">
      <a href="clubs/acm.php" class="text-box"><span>ACM</span></a>
      <a href="clubs/aces.php" class="text-box"><span>ACES</span></a>
      <a href="clubs/cesa.php" class="text-box"><span>CESA</span></a>
      <a href="clubs/mesa.php" class="text-box"><span>MESA</span></a>
      <a href="clubs/itsa.php" class="text-box"><span>ITSA</span></a>
      <a href="clubs/ieee.php" class="text-box"><span>IEEE</span></a>
    </div>
  </section>
</body>
</html>

<?php
require_once 'footer.php'; // optional include
?>
