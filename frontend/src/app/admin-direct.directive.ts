// Aqui crearmos una directiva para el ADMIN
// que se encargara de mostrar u ocultar elementos del DOM

import { Directive } from '@angular/core';
import { Input, TemplateRef, ViewContainerRef } from '@angular/core';

@Directive({
  selector: '[appAdminDirect]',
  standalone: true
})
export class AdminDirectDirective {
  private hasView = false;

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef
  ) { }

  @Input() set appAdminDirect(condition: boolean) {
    if (condition && !this.hasView) {
      this.viewContainer.createEmbeddedView(this.templateRef);
      this.hasView = true;
    } else if (!condition && this.hasView) {
      this.viewContainer.clear();
      this.hasView = false;
    }
  }



}
