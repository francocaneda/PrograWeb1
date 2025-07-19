import { Component } from '@angular/core';
import { RouterOutlet, RouterModule, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';  
import { environment } from '../../environments/environment';
import { AuthService } from '../auth.service';
import { UserService } from '../user.service';
import { RegisUserComponent } from '../regis-user/regis-user.component';
import Swal from 'sweetalert2';


@Component({
  selector: 'app-login',
  standalone: true,
  imports: [RouterOutlet, RouterModule, CommonModule, FormsModule], 
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent {
  public environmentData: any = null;
  public apiSysinfo: any = null;

  email: string = '';
  clave: string = '';
  errorMsg: string = '';
  loading = false;

  constructor(private http: HttpClient, private authService: AuthService, private router: Router, private userService: UserService) {
    this.environmentData = environment;
    this.http.get(environment.apiurl + 'sysinfo').subscribe(
      (response: any) => {
        this.apiSysinfo = response;
        
      },
      (error) => {
        console.error(error);
      }
    );
  }

onSubmit() {
  this.errorMsg = '';
  this.loading = true;  

  this.authService.login({ email: this.email, clave: this.clave }).subscribe({
    next: () => {
      setTimeout(() => {
        this.loading = false;
        this.userService.cargarUsuario(); // Cargar usuario después del login exitoso 
      this.router.navigate(['/main-layout']);  // --> Navegar a main-layout luego del login
      }, 3000);
    },
    error: (err) => {
      setTimeout(() => {
        this.loading = false; 
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Email o contraseña incorrectos',
        });
      }, 2000); 
      this.errorMsg = 'Usuario o contraseña incorrectos';
      console.error('Error login', err);
    },
    });
  }
}
