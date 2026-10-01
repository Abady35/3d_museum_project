<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mısır Müzesi - Antik Sırları Keşfet</title>
    <style>
        /* Genel Ayarlar */
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #050505;
            color: #fff;
            overflow-x: hidden;
        }

        /* 3D Arka Plan Tuvali */
        #hero-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1; /* Yazıların arkasında kalması için */
        }

        /* Üst Menü (Glassmorphism) */
        header {
            position: relative;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            background: rgba(5, 5, 5, 0.6);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #d4af37;
            text-decoration: none;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            margin-left: 30px;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #d4af37;
        }

        .btn-outline {
            border: 1px solid #d4af37;
            padding: 8px 20px;
            border-radius: 4px;
            color: #d4af37 !important;
        }

        .btn-outline:hover {
            background: #d4af37;
            color: #050505 !important;
        }

        /* Hero (Karşılama) İçeriği */
        .hero-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            height: calc(100vh - 80px); /* Header yüksekliğini çıkarıyoruz */
            padding: 0 10%;
            pointer-events: none; /* İçeriğin altındaki 3D alanın fareyi algılaması için */
        }

        .hero-content h1 {
            font-size: 64px;
            margin: 0 0 20px 0;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 5px 15px rgba(0,0,0,0.8);
        }

        .hero-content h1 span {
            color: #d4af37;
        }

        .hero-content p {
            font-size: 18px;
            max-width: 500px;
            line-height: 1.6;
            color: #ccc;
            margin-bottom: 40px;
            pointer-events: auto;
        }

        .cta-buttons {
            pointer-events: auto;
        }

        .btn-primary {
            background: linear-gradient(45deg, #d4af37, #b08d26);
            color: #050505;
            padding: 15px 35px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
            margin-right: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
        }

    </style>

    <!-- Three.js Import Map -->
    <script type="importmap">
        {
            "imports": {
                "three": "https://unpkg.com/three@0.160.0/build/three.module.js"
            }
        }
    </script>
</head>
<body>

    <!-- 3D Arka Plan Tuvali -->
    <div id="hero-canvas"></div>

    <!-- Menü -->
    <header>
        <a href="index.php" class="logo">Mısır 3D</a>
        <div class="nav-links">
            <a href="tours.php">Turları Keşfet</a>
            <a href="../app/login.php">Giriş Yap</a>
            <a href="../app/register.php" class="btn-outline">Kayıt Ol</a>
        </div>
    </header>

    <!-- Karşılama Metni -->
    <div class="hero-content">
        <h1>Firavunların<br><span>Sırrını Çöz</span></h1>
        <p>Gelişmiş 3D modelleme teknolojisi ile Antik Mısır'ın derinliklerine inin. Evinizin rahatlığında tarihi eserleri 360 derece inceleyin ve unutulmaz bir sanal tur deneyimi yaşayın.</p>
        <div class="cta-buttons">
            <a href="../app/register.php" class="btn-primary">Müzeye Katıl</a>
            <a href="tours.php" class="btn-primary" style="background: transparent; border: 2px solid #fff; color: #fff;">Turları İncele</a>
        </div>
    </div>

    <script type="module">
        import * as THREE from 'three';

        // 1. Sahne ve Kamera Ayarları
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        document.getElementById('hero-canvas').appendChild(renderer.domElement);

        // 2. Altın Rengi Wireframe (Tel Kafes) Piramit Oluşturma
        // TetrahedronGeometry 4 yüzlü bir piramit üretir
        const geometry = new THREE.TetrahedronGeometry(3, 0); 
        
        // Sadece çizgilerden oluşan modern ve teknolojik bir görünüm
        const material = new THREE.MeshBasicMaterial({ 
            color: 0xd4af37, 
            wireframe: true,
            transparent: true,
            opacity: 0.8
        });
        const pyramid = new THREE.Mesh(geometry, material);
        
        // Piramidi sağ tarafa doğru konumlandırıyoruz ki yazılarla çakışmasın
        pyramid.position.set(4, 0, -5);
        
        // Piramidi tabanı aşağı gelecek şekilde döndürüyoruz
        pyramid.rotation.x = Math.PI / 4;
        pyramid.rotation.y = Math.PI / 4;
        scene.add(pyramid);

        // 3. Uçuşan Çöl Kumu / Yıldız Parçacıkları (Particles) Sistemi
        const particlesGeometry = new THREE.BufferGeometry();
        const particlesCount = 700; // Parçacık sayısı
        const posArray = new Float32Array(particlesCount * 3);

        for(let i = 0; i < particlesCount * 3; i++) {
            // Ekrana rastgele dağıtılmış parçacıklar
            posArray[i] = (Math.random() - 0.5) * 20;
        }

        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.02,
            color: 0xd4af37, // Altın rengi kum taneleri
            transparent: true,
            opacity: 0.6
        });

        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);

        camera.position.z = 5;

        // Fare Hareketini Yakalama (Parallax efekti için)
        let mouseX = 0;
        let mouseY = 0;
        let targetX = 0;
        let targetY = 0;
        const windowHalfX = window.innerWidth / 2;
        const windowHalfY = window.innerHeight / 2;

        document.addEventListener('mousemove', (event) => {
            mouseX = (event.clientX - windowHalfX);
            mouseY = (event.clientY - windowHalfY);
        });

        // 4. Animasyon Döngüsü
        const clock = new THREE.Clock();

        function animate() {
            requestAnimationFrame(animate);
            const elapsedTime = clock.getElapsedTime();

            // Piramidin kendi etrafında zarifçe dönmesi
            pyramid.rotation.y += 0.005;
            pyramid.rotation.z += 0.002;

            // Parçacıkların (kumların) yukarı doğru uçuşması ve dalgalanması
            particlesMesh.rotation.y = -0.05 * elapsedTime;
            particlesMesh.position.y = Math.sin(elapsedTime * 0.5) * 0.2;

            // Fare hareketine göre yumuşak kamera kaydırması (Parallax)
            targetX = mouseX * 0.001;
            targetY = mouseY * 0.001;
            
            pyramid.rotation.y += 0.05 * (targetX - pyramid.rotation.y);
            pyramid.rotation.x += 0.05 * (targetY - pyramid.rotation.x);

            renderer.render(scene, camera);
        }
        animate();

        // Ekran boyutu değiştiğinde yapıyı koru
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>
</html>