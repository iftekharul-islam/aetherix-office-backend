<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(-45deg, #667eea, #764ba2, #6b73ff, #a3bded);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .login-box {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 40px;
      width: 100%;
      max-width: 400px;
      color: #fff;
      animation: fadeIn 1.5s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    .login-box h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 32px;
      font-weight: bold;
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-group input {
      width: 100%;
      padding: 14px 12px;
      border: none;
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
      font-size: 16px;
      outline: none;
      transition: 0.3s ease;
    }

    .form-group input::placeholder {
      color: transparent;
    }

    .form-group input:focus {
      background-color: rgba(255, 255, 255, 0.2);
      box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    }

    .form-group label {
      position: absolute;
      top: 12px;
      left: 14px;
      color: #e0e0e0;
      font-size: 15px;
      pointer-events: none;
      transition: 0.2s ease all;
    }

    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label {
      top: -20px;
      left: 10px;
      font-size: 12px;
      color: #ffffff;
      /* background: rgba(255,255,255,0.1); */
      padding: 0 5px;
      border-radius: 4px;
    }

    .btn {
      width: 100%;
      padding: 14px;
      background-color: rgba(255, 255, 255, 0.3);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn:hover {
      background-color: rgba(255, 255, 255, 0.4);
      transform: translateY(-1px);
    }

    .btn:active {
      transform: scale(0.98);
    }

    .error-box {
      background-color: rgba(255, 0, 0, 0.15);
      color: #ffe0e0;
      padding: 12px;
      border-radius: 6px;
      font-size: 14px;
      margin-bottom: 20px;
    }

    .error-box ul {
      margin: 0;
      padding-left: 20px;
    }
  </style>
</head>
<body>

  <div class="login-box">
    <h2>Welcome Back</h2>

    @if ($errors->any())
      <div class="error-box">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
      @csrf

      <div class="form-group">
        <input type="email" name="email" id="email" placeholder=" " required autofocus>
        <label for="email">Email Address</label>
      </div>

      <div class="form-group">
        <input type="password" name="password" id="password" placeholder=" " required>
        <label for="password">Password</label>
      </div>

      <button type="submit" class="btn">Login</button>
    </form>
  </div>

</body>
</html>
