<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="logo.png">
    <title>Harris Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }

        body {

            font-family: 'Poppins', sans-serif;

            background: #ffffff;


            min-height: 100vh;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            padding: 20px;

        }

        .header {

            text-align: center;

            margin-bottom: 40px;

            color: #2c3e50;

        }

        .header h1 {

            font-size: 2.5rem;

            font-weight: 600;

            margin-bottom: 10px;

        }

        .header p {

            color: #7f8c8d;

            font-size: 1rem;

        }

        .training-container {

            display: flex;

            justify-content: center;

            gap: 30px;

            flex-wrap: wrap;

        }

        .training-card {

            width: 300px;

            height: 400px;

            background: white;

            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.1);

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            text-align: center;

            transition: all 0.3s ease;

            cursor: pointer;

            position: relative;

            overflow: hidden;

            margin-bottom: 20px;

        }

        .training-card::before {

            content: '';

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: linear-gradient(45deg, transparent, #3498db, transparent);

            transform: rotate(-45deg);

            opacity: 0;

            transition: all 0.5s ease;

        }

        .training-card:hover::before {

            opacity: 0.1;

        }

        .training-card:hover {

            transform: translateY(-10px);

            box-shadow: 0 15px 40px rgba(0,0,0,0.15);

        }

        .card-icon {

            font-size: 4rem;

            color: #FF8C42;

            margin-bottom: 20px;

            transition: color 0.3s ease;

        }

        .training-card:hover .card-icon {

            color: #FF6B35;

        }

        .card-title {

            font-size: 1.5rem;

            font-weight: 600;

            color: #2c3e50;

            margin-bottom: 15px;

        }

        .card-desc {

            font-size: 1rem;

            color: #7f8c8d;

            padding: 0 30px;

            margin-bottom: 20px;

        }

        .card-btn {

            padding: 10px 25px;

            background-color: #FF8C42;

            color: white;

            border-radius: 25px;

            text-decoration: none;

            transition: all 0.3s ease;

        }

        .training-card:hover .card-btn {

            background-color: #FF6B35;

        }


        @media screen and (max-width: 768px) {

            .header h1 {

                font-size: 2rem;

            }

            .training-container {

                flex-direction: column;

                align-items: center;

            }

            .training-card {

                width: 100%;

                max-width: 350px;

            }

        }


        @media screen and (max-width: 480px) {

            .header h1 {

                font-size: 1.5rem;

            }

            .card-icon {

                font-size: 3rem;

            }

            .card-title {

                font-size: 1.2rem;

            }

            .card-desc {

                font-size: 0.9rem;

                padding: 0 20px;

            }

        }
         .header {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .header img {
            max-width: 350px;
            height: auto;
        }

    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

   <div class="header">
            <img src="logo.png" alt="Logo Harris Hotel">
        </div>



    <div class="training-container">

        <div class="training-card" onclick="window.location.href='login_user'">

            <div class="card-icon">

                <i class="fas fa-user-tie"></i>

            </div>

            <div class="card-title">Karyawan</div>

            <div class="card-desc">

                Portal untuk karyawan melakukan pelaporan dan mengakses informasi training <br>
                        <!--sedang dalam pengembangan seharusnya besok sudah bisa di pakai <br> mohon bersabar-->
            </div>

            <a href="user" class="card-btn">Masuk</a>

        </div>


        <div class="training-card" onclick="window.location.href='login'">

            <div class="card-icon">

                <i class="fas fa-user-shield"></i>

            </div>

            <div class="card-title">Admin</div>

            <div class="card-desc">

                Pusat kendali untuk manajemen training dan monitoring laporan karyawan

            </div>

            <a href="login" class="card-btn">Masuk</a>

        </div>

    </div>

</body>

</html>