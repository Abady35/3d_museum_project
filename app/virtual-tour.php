<?php
require_once 'auth_check.php';
require_once '../config/database.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['code'])) {
    die("Hata: Bilet kodu bulunamadı.");
}

$ticket_code = $_GET['code'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT tour_id FROM bookings WHERE ticket_code = ? AND user_id = ? AND status = 'confirmed'");
$stmt->execute([$ticket_code, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Bu tura erişim yetkiniz yok veya geçersiz bilet.");
}

$tour_id = $booking['tour_id'];
$stmt_artifacts = $pdo->prepare("SELECT name, historical_info, model_3d_url, scale_factor FROM artifacts WHERE tour_id = ?");
$stmt_artifacts->execute([$tour_id]);
$artifacts = $stmt_artifacts->fetchAll();

$artifacts_json = json_encode($artifacts ? $artifacts : []);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>3D Müze - Ultra Detaylı Galeri</title>
    <style>
        body { margin: 0; overflow: hidden; background-color: #eaeaea; color: #333; font-family: 'Segoe UI', sans-serif; }
        #canvas-container { width: 100vw; height: 100vh; position: absolute; top: 0; left: 0; z-index: 1; }
        
        #ui-layer {
            position: absolute; bottom: 30px; left: 30px; z-index: 10;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.5); padding: 25px;
            border-radius: 12px; max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        #artifact-title { color: #b08d26; margin: 0 0 10px 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        #artifact-info { font-size: 14px; line-height: 1.6; color: #555; margin-bottom: 20px; min-height: 60px; }
        
        #nav-container {
            display: flex; justify-content: space-between; align-items: center;
            border-top: 1px solid rgba(212,175,55,0.3); padding-top: 15px;
        }
        .nav-btn {
            background: #d4af37; color: #000; border: none; padding: 8px 15px;
            border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .nav-btn:hover { background: #b08d26; color: #fff; }
        .nav-btn-outline {
            background: transparent; color: #b08d26; border: 1px solid #b08d26;
            padding: 8px 15px; border-radius: 6px; cursor: pointer; transition: 0.3s;
        }
        .nav-btn-outline:hover { background: #b08d26; color: #fff; }

        .back-btn {
            position: absolute; top: 20px; right: 20px; z-index: 10;
            padding: 10px 20px; background: #d4af37; color: #000;
            text-decoration: none; font-weight: bold; border-radius: 6px; transition: 0.3s;
            box-shadow: 0 3px 6px rgba(0,0,0,0.16);
        }
        .back-btn:hover { background: #000; color: #d4af37; transform: translateY(-2px); }
    </style>

    <script type="importmap">
        {
            "imports": {
                "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
                "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
            }
        }
    </script>
</head>
<body>

    <a href="dashboard.php" class="back-btn">⬅ Galeriye Dön</a>
    
    <div id="ui-layer">
        <h2 id="artifact-title">Yükleniyor...</h2>
        <p id="artifact-info">Müze galerisi hazırlanıyor...</p>
        
        <div id="nav-container" style="display: none;">
            <button id="prev-btn" class="nav-btn-outline">⬅ Önceki</button>
            <span id="counter" style="color: #888; font-size: 14px; font-weight: bold;">1 / 1</span>
            <button id="next-btn" class="nav-btn">Sonraki ➡</button>
        </div>
    </div>

    <div id="canvas-container"></div>

<script type="module">
    import * as THREE from 'three';
    import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
    import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
    // YENİ: Gerçekçi materyal yansımaları için sanal stüdyo ortamı eklentisi
    import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

    const artifactsData = <?php echo $artifacts_json; ?>;
    
    let currentIndex = 0;
    let currentModelGroup = null;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xeaeaea);

    const camera = new THREE.PerspectiveCamera(40, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 5); 

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.tonMapping = THREE.ACESFilmicToneMapping;
    renderer.tonMappingExposure = 1.0; 
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    document.getElementById('canvas-container').appendChild(renderer.domElement);

    // --- YENİ: SANAL STÜDYO ORTAMI (Koyu modellerin detaylarını patlatır) ---
    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    scene.environment = pmremGenerator.fromScene(new RoomEnvironment(), 0.04).texture;

    // --- AYDINLATMA ---
    // Ortam yansıması olduğu için ışıkları biraz dengeledik
    scene.add(new THREE.AmbientLight(0xffffff, 0.6)); 

    const mainLight = new THREE.DirectionalLight(0xffffff, 1.2);
    mainLight.position.set(5, 10, 7.5);
    scene.add(mainLight);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;

    // --- ESER YÜKLEME FONKSİYONU ---
    function loadArtifact(index) {
        if (artifactsData.length === 0) return;
        const current = artifactsData[index];

        document.getElementById('artifact-title').innerText = current.name;
        document.getElementById('artifact-info').innerText = current.historical_info;
        document.getElementById('counter').innerText = (index + 1) + " / " + artifactsData.length;

        if (currentModelGroup) {
            scene.remove(currentModelGroup);
            currentModelGroup = null;
        }

        const loader = new GLTFLoader();
        loader.load(
            current.model_3d_url, 
            function (gltf) {
                const model = gltf.scene;
                
                currentModelGroup = new THREE.Group();
                currentModelGroup.add(model);
                scene.add(currentModelGroup);

                const initialBox = new THREE.Box3().setFromObject(model);
                const initialSize = initialBox.getSize(new THREE.Vector3());
                const maxDim = Math.max(initialSize.x, initialSize.y, initialSize.z);
                const targetScale = (3 / maxDim) * (current.scale_factor || 1);
                
                currentModelGroup.scale.set(targetScale, targetScale, targetScale);
                currentModelGroup.updateMatrixWorld(true);
                
                const finalBox = new THREE.Box3().setFromObject(currentModelGroup);
                const center = finalBox.getCenter(new THREE.Vector3());
                
                currentModelGroup.position.x = -center.x;
                currentModelGroup.position.y = -center.y;
                currentModelGroup.position.z = -center.z;

                // --- MATERYAL DETAYLANDIRMA DÖNGÜSÜ ---
                model.traverse(n => { 
                    if(n.isMesh) { 
                        n.castShadow = true; 
                        n.receiveShadow = true; 
                        // Sütun gibi koyu modellerin yansıma şiddetini zorla artırıyoruz
                        if(n.material) {
                            n.material.envMapIntensity = 1.5; // Ortam yansımasını güçlendir
                            n.material.needsUpdate = true;
                        }
                    } 
                });

                camera.position.z = 5;
                camera.lookAt(0, 0, 0);
                controls.target.set(0, 0, 0);
                controls.update();
            }, 
            undefined,
            function (err) { 
                console.error("Yükleme hatası:", err);
                document.getElementById('artifact-info').innerText = "Model yüklenemedi. Yol: " + current.model_3d_url;
            }
        );
    }

    if (artifactsData.length > 0) {
        if (artifactsData.length > 1) {
            document.getElementById('nav-container').style.display = 'flex';
        }
        loadArtifact(currentIndex);
    } else {
        document.getElementById('artifact-title').innerText = "Eser Bulunamadı";
        document.getElementById('artifact-info').innerText = "Bu turda henüz eklenmiş bir 3D eser bulunmuyor.";
    }

    document.getElementById('next-btn').addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % artifactsData.length;
        loadArtifact(currentIndex);
    });

    document.getElementById('prev-btn').addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + artifactsData.length) % artifactsData.length;
        loadArtifact(currentIndex);
    });

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
</body>
</html>