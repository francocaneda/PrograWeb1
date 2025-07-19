import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-password-recup',
  standalone: true,
  imports: [RouterModule, FormsModule, HttpClientModule, CommonModule],
  templateUrl: './password-recup.component.html',
  styleUrls: ['./password-recup.component.css']
})
export class PasswordRecupComponent {
  email: string = '';
  loading = false;

  constructor(private http: HttpClient) {}

  onSubmit() {
    if (!this.email) {
      Swal.fire('Error', 'Debe ingresar un email válido', 'error');
      return;
    }

    this.loading = true;

    this.http.post('http://localhost:8012/miproyecto/api/reset-password.php?comando=reset', { email: this.email })
      .subscribe({
        next: (res: any) => {
            setTimeout(() => {
        this.loading = false; 
          Swal.fire('Éxito', 'Se ha enviado un correo con el enlace de recuperación', 'success');
          this.email = '';
      }, 1000);
        },
        error: (err) => {
          this.loading = false;
          if (err.status === 404) {
            Swal.fire('Error', 'El email no está registrado', 'error');
          } else {
            Swal.fire('Error', 'Ocurrió un error al enviar el correo', 'error');
          }
        }
      });
  }
}
