CREATE TABLE venta_pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_venta_pagos_venta
        FOREIGN KEY (id_venta) REFERENCES ventas(id_venta)
);
