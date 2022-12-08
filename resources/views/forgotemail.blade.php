<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Forgor Password</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="{{url('')}}/public/vendors/feather/feather.css">
  <link rel="stylesheet" href="{{url('')}}/public/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="{{url('')}}/public/vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="{{url('')}}/public/vendors/select2/select2.min.css">
  <link rel="stylesheet" href="{{url('')}}/public/vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="{{url('')}}/public/css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="{{url('')}}/public/images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <!-- partial:{{url('')}}/public/partials/_navbar.html -->

    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:{{url('')}}/public/partials/_settings-panel.html -->


      <!-- partial -->
      <!-- partial:{{url('')}}/public/partials/_sidebar.html -->

      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (session()->has('message'))
                                    <div class="alert alert-success">
                                        {{ session()->get('message') }}
                                    </div>
                                @endif
                                @if (session()->has('error'))
                                    <div class="alert alert-danger">
                                        {{ session()->get('error') }}
                                    </div>
                                @endif
                  <h4 class="card-title">Reset Password</h4>
                  <p class="card-description">
                    Fill in your new password
                  </p>
                  <form class="forms-sample" action="/forgot-password-now" method="POST">
                    @csrf

                    <div class="form-group">
                      <label for="email">Email</label>
                      <input type="email" readonly class="form-control" name="email" id="email" value="{{$email}}" autofocus >
                    </div>

                    <div class="form-group">
                        <label for="email">Password</label>
                        <input type="password" required class="form-control" name="password" id="email" placeholder="Enter new password" autofocus >
                      </div>

                      <div class="form-group">
                        <label for="email">Confirm Password</label>
                        <input type="password" required class="form-control" name="password_confirmation" id="email" placeholder="Confirm Password" autofocus >
                      </div>



                    <button type="submit" class="btn btn-primary mr-2">Submit</button>
                  </form>
                </div>
              </div>
            </div>


          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:{{url('')}}/public/partials/_footer.html -->

        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="{{url('')}}/public/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="{{url('')}}/public/vendors/typeahead.js/typeahead.bundle.min.js"></script>
  <script src="{{url('')}}/public/vendors/select2/select2.min.js"></script>
  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="{{url('')}}/public/js/off-canvas.js"></script>
  <script src="{{url('')}}/public/js/hoverable-collapse.js"></script>
  <script src="{{url('')}}/public/js/template.js"></script>
  <script src="{{url('')}}/public/js/settings.js"></script>
  <script src="{{url('')}}/public/js/todolist.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="{{url('')}}/public/js/file-upload.js"></script>
  <script src="{{url('')}}/public/js/typeahead.js"></script>
  <script src="{{url('')}}/public/js/select2.js"></script>
  <!-- End custom js for this page-->
</body>

</html>
