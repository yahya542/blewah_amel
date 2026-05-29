
---

## Alur AHP → COCOSO

```
┌─────────────────────────────────────────────────────────────┐
│  AHP (Menghitung Bobot)                                     │
├─────────────────────────────────────────────────────────────┤
│  Input: Perbandingan kriteria (1-9)                        │
│  Proses: Pairwise Comparison → Eigenvector                 │
│  Output: Bobot (w) untuk setiap kriteria                  │
│           ↓ Simpan ke database                             │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│  COCOSO (Meranking Alternatif)                              │
├─────────────────────────────────────────────────────────────┤
│  Input: Nilai alternatif per kriteria + Bobot dari AHP    │
│                                                         │
│  1. Normalisasi Matriks (Min-Max)                         │
│                                                         │
│  2. Hitung Si = Σ (r_ij × w_j)    ← Weighted Sum         │
│                                                         │
│  3. Hitung Pi = ∏ (r_ij)^w_j     ← Weighted Product      │
│                                                         │
│  4. Hitung:                                               │
│      k_a = (Si + Pi) / Σ(Si + Pi)                          │
│      k_b = Si / min(Si)                                   │
│      k_c = 0.5·(Si/maxSi) + 0.5·(Pi/maxPi)               │
│                                                         │
│  5. Qi = (k_a × k_b × k_c)^(1/3) + (k_a+k_b+k_c)/3      │
│       ↑ Skor akhir (semakin tinggi = semakin baik)       │
│                                                         │
│  Output: Ranking alternatif                               │
└─────────────────────────────────────────────────────────────┘
```

---

## Ringkasan

| Tahap | Output |
|-------|--------|
| AHP | Bobot kriteria (weight) |
| COCOSO | Ranking alternatif (Qi) |


1. `/criteria` → tambah kriteria
2. `/alternative` → tambah varietas
3. `/ahp` → input perbandingan, hitung bobot
4. `/cocoso` → input nilai varietas, hitung ranking
