import numpy as np
mt = np.random.randint(-10, 10, size = (5,6))
mt2 = mt.copy()
mt2[mt2 < 0] =0
print(mt)
print(mt2)
