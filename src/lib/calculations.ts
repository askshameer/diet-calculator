export interface UserData {
  height: number; // cm
  weight: number; // kg
  age: number;
  sex: 'male' | 'female';
  exerciseIntensity: 'low' | 'moderate' | 'high' | 'very_high';
  dailyActivityLevel: 'sedentary' | 'lightly_active' | 'moderately_active' | 'very_active' | 'extremely_active';
  goal: 'lose_weight' | 'build_muscle' | 'athletic_performance' | 'body_recomposition' | 'improve_health';
  goalWeight?: number;
  timeline: number; // weeks
}

// Calculate Basal Metabolic Rate using Mifflin-St Jeor Equation
export function calculateBMR(height: number, weight: number, age: number, sex: 'male' | 'female'): number {
  const base = 10 * weight + 6.25 * height - 5 * age;
  return sex === 'male' ? base + 5 : base - 161;
}

// Calculate Total Daily Energy Expenditure
export function calculateTDEE(bmr: number, activityLevel: string, exerciseIntensity: string): number {
  let activityMultiplier = 1.2; // sedentary
  
  switch (activityLevel) {
    case 'lightly_active':
      activityMultiplier = 1.375;
      break;
    case 'moderately_active':
      activityMultiplier = 1.55;
      break;
    case 'very_active':
      activityMultiplier = 1.725;
      break;
    case 'extremely_active':
      activityMultiplier = 1.9;
      break;
  }
  
  // Adjust for exercise intensity
  let exerciseMultiplier = 1.0;
  switch (exerciseIntensity) {
    case 'moderate':
      exerciseMultiplier = 1.1;
      break;
    case 'high':
      exerciseMultiplier = 1.2;
      break;
    case 'very_high':
      exerciseMultiplier = 1.3;
      break;
  }
  
  return bmr * activityMultiplier * exerciseMultiplier;
}

// Calculate daily calorie needs based on goal
export function calculateDailyCalories(tdee: number, goal: string, currentWeight: number, goalWeight?: number, timeline?: number): number {
  let calorieAdjustment = 0;
  
  if (goal === 'lose_weight' && goalWeight && timeline) {
    const weightToLose = currentWeight - goalWeight;
    const weeksToLose = timeline;
    const caloriesPerKg = 7700; // approximate calories per kg of fat
    const weeklyCalorieDeficit = (weightToLose * caloriesPerKg) / weeksToLose;
    const dailyCalorieDeficit = weeklyCalorieDeficit / 7;
    calorieAdjustment = -dailyCalorieDeficit;
  } else if (goal === 'build_muscle') {
    calorieAdjustment = 300; // moderate surplus for muscle building
  } else if (goal === 'body_recomposition') {
    calorieAdjustment = 0; // maintain current calories for recomp
  } else if (goal === 'athletic_performance') {
    calorieAdjustment = 200; // slight surplus for performance
  }
  
  return Math.max(1200, tdee + calorieAdjustment); // minimum 1200 calories
}

// Calculate macronutrient distribution
export function calculateMacros(dailyCalories: number, macroRatio: string, goal: string): {
  protein: number;
  carbs: number;
  fat: number;
} {
  let proteinRatio = 0.3;
  let carbRatio = 0.4;
  let fatRatio = 0.3;
  
  switch (macroRatio) {
    case 'high_protein':
      proteinRatio = 0.4;
      carbRatio = 0.3;
      fatRatio = 0.3;
      break;
    case 'low_carb':
      proteinRatio = 0.35;
      carbRatio = 0.15;
      fatRatio = 0.5;
      break;
    case 'high_carb':
      proteinRatio = 0.2;
      carbRatio = 0.6;
      fatRatio = 0.2;
      break;
  }
  
  // Adjust based on goal
  if (goal === 'build_muscle') {
    proteinRatio = Math.max(proteinRatio, 0.35);
  }
  
  return {
    protein: (dailyCalories * proteinRatio) / 4, // 4 calories per gram
    carbs: (dailyCalories * carbRatio) / 4,     // 4 calories per gram
    fat: (dailyCalories * fatRatio) / 9         // 9 calories per gram
  };
}

// Calculate water intake recommendation
export function calculateWaterIntake(weight: number, exerciseIntensity: string): number {
  let baseWater = weight * 35; // ml per kg body weight
  
  switch (exerciseIntensity) {
    case 'moderate':
      baseWater *= 1.2;
      break;
    case 'high':
      baseWater *= 1.4;
      break;
    case 'very_high':
      baseWater *= 1.6;
      break;
  }
  
  return Math.round(baseWater);
}