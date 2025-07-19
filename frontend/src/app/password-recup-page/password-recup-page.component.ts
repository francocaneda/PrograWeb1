import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-password-recup-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './password-recup-page.component.html',
  styleUrls: ['./password-recup-page.component.css']
})
export class PasswordRecupPageComponent {
  clave: string = '';
  reclave: string = '';
  loading: boolean = false;
  token: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient,
    private router: Router
  ) {
    this.token = this.route.snapshot.queryParamMap.get('token');
  }

  onSubmit(form: any) {
    if (form.invalid) return;

    if (this.clave !== this.reclave) {
      Swal.fire('Error', 'Las contraseñas no coinciden', 'error');
      return;
    }

    if (!this.token) {
      Swal.fire('Error', 'Token inválido o inexistente', 'error');
      return;
    }

    this.loading = true;

    const body = {
      token: this.token,
      newPassword: this.clave
    };

    this.http.patch('http://localhost:8012/miproyecto/api/reset-password.php?comando=UpdatePassword', body)
      .subscribe({
        next: () => {
          setTimeout(() => {
            this.loading = false;
            Swal.fire('Éxito', 'Contraseña cambiada correctamente', 'success');
            this.router.navigate(['/loginscreen']);
          }, 2000);
        },
        error: (err) => {
          this.loading = false;
          Swal.fire('Error', err.error?.message || 'Error al cambiar la contraseña', 'error');
        }
      });
  }
}
