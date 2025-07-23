import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { AuthService } from '../auth.service';

@Injectable({
  providedIn: 'root'
})
export class AdminGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(): boolean {
    const rol = this.authService.getRol();
    const estaAutenticado = this.authService.estaAutenticado();

    if (estaAutenticado && rol === 'admin') {
      return true;
    }

    // Si no es admin, redirigimos al index
    this.router.navigate(['/main-layout/index']);
    return false;
  }
}
