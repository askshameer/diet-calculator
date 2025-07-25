import jsPDF from 'jspdf'

// Food category indicators for visual clarity without emojis

// Helper function to clean text and remove problematic characters
const cleanText = (text: string): string => {
  // Remove all non-ASCII characters and emojis that cause encoding issues
  let cleaned = text
    .replace(/[^\x20-\x7E]/g, '') // Keep only basic ASCII printable characters
    .replace(/\s+/g, ' ')
    .trim()
  
  // Add simple text-based food indicators instead of emojis
  const foodReplacements: { [key: string]: string } = {
    'chicken': '(Protein) chicken',
    'salmon': '(Fish) salmon', 
    'eggs': '(Protein) eggs',
    'yogurt': '(Dairy) yogurt',
    'spinach': '(Vegetable) spinach',
    'broccoli': '(Vegetable) broccoli',
    'quinoa': '(Grain) quinoa',
    'oats': '(Grain) oats',
    'berries': '(Fruit) berries',
    'avocado': '(Healthy Fat) avocado'
  }
  
  // Apply food category labels
  Object.entries(foodReplacements).forEach(([food, replacement]) => {
    const regex = new RegExp(`\\b${food}\\b`, 'gi')
    if (regex.test(cleaned)) {
      cleaned = cleaned.replace(regex, replacement)
    }
  })
  
  return cleaned
}

interface PDFData {
  // Personal Info
  height: number
  weight: number
  age: number
  sex: string
  
  // Goals
  goal: string
  timeline: number
  goalWeight?: number
  
  // Activity
  exerciseIntensity: string
  dailyActivityLevel: string
  
  // Preferences
  dietaryPreferences: string[]
  foodAllergies: string[]
  foodIntolerances: string[]
  macronutrientRatio: string
  mealsPerDay: number
  
  // Calculated Results
  bmr: number
  tdee: number
  dailyCalories: number
  proteinGrams: number
  carbGrams: number
  fatGrams: number
  waterIntake: number
  
  // AI Generated Content
  mealPlan: any
}

export function generatePDFReport(data: PDFData): void {
  const pdf = new jsPDF('p', 'mm', 'a4')
  const pageWidth = pdf.internal.pageSize.width
  const pageHeight = pdf.internal.pageSize.height
  let currentY = 20
  
  // Helper function to add text with word wrapping and character cleaning
  const addText = (text: string, x: number, y: number, maxWidth?: number, fontSize?: number) => {
    const cleanedText = cleanText(text)
    if (fontSize) pdf.setFontSize(fontSize)
    if (maxWidth) {
      const splitText = pdf.splitTextToSize(cleanedText, maxWidth)
      pdf.text(splitText, x, y)
      return y + (splitText.length * (fontSize || 12) * 0.4)
    } else {
      pdf.text(cleanedText, x, y)
      return y + (fontSize || 12) * 0.4
    }
  }

  // Add colorful background gradients
  const addColorfulHeader = (title: string, y: number, color: [number, number, number]) => {
    // Add colored rectangle background
    pdf.setFillColor(color[0], color[1], color[2])
    pdf.rect(15, y - 8, pageWidth - 30, 15, 'F')
    
    // Add white text on colored background
    pdf.setTextColor(255, 255, 255)
    pdf.setFontSize(16)
    pdf.setFont('helvetica', 'bold')
    pdf.text(cleanText(title), 20, y)
    
    // Reset text color to black
    pdf.setTextColor(0, 0, 0)
    
    return y + 12
  }
  
  // Header
  pdf.setFontSize(24)
  pdf.setFont('helvetica', 'bold')
  currentY = addText('Personalized Diet & Nutrition Report', 20, currentY, undefined, 24)
  
  pdf.setFontSize(12)
  pdf.setFont('helvetica', 'normal')
  currentY = addText(`Generated on: ${new Date().toLocaleDateString()}`, 20, currentY + 10)
  
  // Add a line
  pdf.line(20, currentY + 5, pageWidth - 20, currentY + 5)
  currentY += 15
  
  // Personal Information Section
  pdf.setFontSize(16)
  pdf.setFont('helvetica', 'bold')
  currentY = addText('Personal Information', 20, currentY, undefined, 16)
  currentY += 5
  
  pdf.setFontSize(11)
  pdf.setFont('helvetica', 'normal')
  const personalInfo = [
    `Age: ${data.age} years, Sex: ${data.sex.charAt(0).toUpperCase() + data.sex.slice(1)}`,
    `Height: ${data.height} cm, Weight: ${data.weight} kg`,
    `Primary Goal: ${data.goal.replace('_', ' ').replace(/\\b\\w/g, l => l.toUpperCase())}`,
    `Timeline: ${data.timeline} weeks`,
    data.goalWeight ? `Goal Weight: ${data.goalWeight} kg` : '',
    `Activity Level: ${data.dailyActivityLevel.replace('_', ' ')}`,
    `Exercise Intensity: ${data.exerciseIntensity.replace('_', ' ')}`
  ].filter(Boolean)
  
  personalInfo.forEach(info => {
    currentY = addText(`• ${info}`, 25, currentY)
    currentY += 2
  })
  
  currentY += 10
  
  // Nutrition Targets Section
  pdf.setFontSize(16)
  pdf.setFont('helvetica', 'bold')
  currentY = addText('Daily Nutrition Targets', 20, currentY, undefined, 16)
  currentY += 5
  
  pdf.setFontSize(11)
  pdf.setFont('helvetica', 'normal')
  
  // Create a table-like structure for nutrition info
  const nutritionData = [
    ['Metric', 'Target', 'Notes'],
    ['Calories', `${Math.round(data.dailyCalories)}`, 'Total daily energy intake'],
    ['Protein', `${Math.round(data.proteinGrams)}g`, 'Muscle maintenance & growth'],
    ['Carbohydrates', `${Math.round(data.carbGrams)}g`, 'Primary energy source'],
    ['Fat', `${Math.round(data.fatGrams)}g`, 'Essential fatty acids & vitamins'],
    ['Water', `${Math.round(data.waterIntake)}ml`, 'Hydration target'],
    ['BMR', `${Math.round(data.bmr)}`, 'Basal Metabolic Rate'],
    ['TDEE', `${Math.round(data.tdee)}`, 'Total Daily Energy Expenditure']
  ]
  
  const colWidths = [40, 30, 90]
  const rowHeight = 6
  
  nutritionData.forEach((row, index) => {
    if (index === 0) {
      pdf.setFont('helvetica', 'bold')
    } else {
      pdf.setFont('helvetica', 'normal')
    }
    
    row.forEach((cell, colIndex) => {
      const x = 20 + colWidths.slice(0, colIndex).reduce((a, b) => a + b, 0)
      pdf.text(cell, x, currentY)
    })
    
    if (index === 0) {
      pdf.line(20, currentY + 2, 20 + colWidths.reduce((a, b) => a + b, 0), currentY + 2)
    }
    
    currentY += rowHeight
  })
  
  currentY += 10
  
  // Dietary Preferences Section
  if (data.dietaryPreferences.length > 0 || data.foodAllergies.length > 0 || data.foodIntolerances.length > 0) {
    pdf.setFontSize(16)
    pdf.setFont('helvetica', 'bold')
    currentY = addText('Dietary Requirements', 20, currentY, undefined, 16)
    currentY += 5
    
    pdf.setFontSize(11)
    pdf.setFont('helvetica', 'normal')
    
    if (data.dietaryPreferences.length > 0) {
      currentY = addText(`Preferences: ${data.dietaryPreferences.join(', ')}`, 25, currentY, pageWidth - 50)
      currentY += 3
    }
    
    if (data.foodAllergies.length > 0) {
      currentY = addText(`Allergies: ${data.foodAllergies.join(', ')}`, 25, currentY, pageWidth - 50)
      currentY += 3
    }
    
    if (data.foodIntolerances.length > 0) {
      currentY = addText(`Intolerances: ${data.foodIntolerances.join(', ')}`, 25, currentY, pageWidth - 50)
      currentY += 3
    }
    
    currentY += 10
  }
  
  // Check if we need a new page
  if (currentY > pageHeight - 50) {
    pdf.addPage()
    currentY = 20
  }
  
  // Meal Plan Section
  if (data.mealPlan?.weeklyPlan) {
    pdf.setFontSize(16)
    pdf.setFont('helvetica', 'bold')
    currentY = addText('Sample Daily Meal Plan', 20, currentY, undefined, 16)
    currentY += 5
    
    pdf.setFontSize(11)
    pdf.setFont('helvetica', 'normal')
    
    const sampleDay = data.mealPlan.weeklyPlan[0]
    if (sampleDay?.meals) {
      sampleDay.meals.forEach((meal: any, index: number) => {
        // Check if we need a new page
        if (currentY > pageHeight - 40) {
          pdf.addPage()
          currentY = 20
        }
        
        pdf.setFont('helvetica', 'bold')
        currentY = addText(`${meal.name}:`, 25, currentY)
        
        pdf.setFont('helvetica', 'normal')
        currentY = addText(`${meal.food}`, 25, currentY + 4)
        currentY = addText(`Calories: ${meal.calories} | Protein: ${meal.protein}g | Carbs: ${meal.carbs}g | Fat: ${meal.fat}g`, 25, currentY + 4, pageWidth - 50)
        currentY = addText(`Portions: ${meal.portions}`, 25, currentY + 4, pageWidth - 50)
        
        currentY += 8
      })
    }
  }
  
  // Food Categorization Section (as requested by user)
  if (data.mealPlan?.foodCategorization) {
    // Check if we need a new page
    if (currentY > pageHeight - 80) {
      pdf.addPage()
      currentY = 20
    }
    
    currentY = addColorfulHeader('FOOD CATEGORIZATION GUIDE', currentY, [46, 125, 50])
    currentY += 3
    
    pdf.setFontSize(10)
    pdf.setFont('helvetica', 'italic')
    currentY = addText('AI-generated food recommendations based on your specific goals and restrictions', 20, currentY, pageWidth - 40)
    currentY += 8
    
    const categories = ['prioritize', 'neutral', 'minimize']
    const categoryColors = {
      prioritize: [0, 150, 0], // Green
      neutral: [0, 0, 150],    // Blue  
      minimize: [150, 0, 0]    // Red
    }
    
    categories.forEach(categoryKey => {
      const category = data.mealPlan.foodCategorization[categoryKey]
      if (!category) return
      
      // Check if we need a new page
      if (currentY > pageHeight - 50) {
        pdf.addPage()
        currentY = 20
      }
      
      // Category header with color
      pdf.setFontSize(14)
      pdf.setFont('helvetica', 'bold')
      const color = categoryColors[categoryKey as keyof typeof categoryColors]
      pdf.setTextColor(color[0], color[1], color[2])
      currentY = addText(category.title, 25, currentY, undefined, 14)
      
      // Reset color to black
      pdf.setTextColor(0, 0, 0)
      
      // Category description
      pdf.setFontSize(10)
      pdf.setFont('helvetica', 'normal')
      currentY = addText(category.description, 25, currentY + 4, pageWidth - 50)
      currentY += 6
      
      // Foods list in columns for better space usage
      pdf.setFontSize(10)
      const foods = category.foods || []
      const midPoint = Math.ceil(foods.length / 2)
      const leftColumn = foods.slice(0, midPoint)
      const rightColumn = foods.slice(midPoint)
      
      let maxLeftY = currentY
      leftColumn.forEach((food: string) => {
        if (maxLeftY > pageHeight - 15) {
          pdf.addPage()
          maxLeftY = 20
        }
        maxLeftY = addText(`• ${food}`, 30, maxLeftY, (pageWidth - 60) / 2)
        maxLeftY += 4
      })
      
      let maxRightY = currentY
      rightColumn.forEach((food: string) => {
        if (maxRightY > pageHeight - 15) {
          // Don't add new page here, just continue on next available space
        }
        const rightColumnX = 30 + (pageWidth - 60) / 2
        maxRightY = addText(`• ${food}`, rightColumnX, maxRightY, (pageWidth - 60) / 2)
        maxRightY += 4
      })
      
      currentY = Math.max(maxLeftY, maxRightY) + 3
      
      // Reasoning
      if (category.reasoning) {
        pdf.setFontSize(9)
        pdf.setFont('helvetica', 'italic')
        currentY = addText(`Why: ${category.reasoning}`, 25, currentY, pageWidth - 50)
        currentY += 8
      }
    })
  }

  // Shopping List Section
  if (data.mealPlan?.shoppingList) {
    // Check if we need a new page
    if (currentY > pageHeight - 60) {
      pdf.addPage()
      currentY = 20
    }
    
    currentY = addColorfulHeader('SMART SHOPPING LIST', currentY, [52, 152, 219])
    currentY += 5
    
    pdf.setFontSize(11)
    pdf.setFont('helvetica', 'normal')
    
    // Handle new shopping list structure
    if (data.mealPlan.shoppingList.proteins) {
      // New detailed structure
      Object.entries(data.mealPlan.shoppingList).forEach(([categoryKey, categoryData]: [string, any]) => {
        if (!categoryData || typeof categoryData !== 'object' || !categoryData.title) return
        
        if (currentY > pageHeight - 30) {
          pdf.addPage()
          currentY = 20
        }
        
        pdf.setFont('helvetica', 'bold')
        currentY = addText(categoryData.title, 25, currentY)
        
        if (categoryData.weeklyTarget) {
          pdf.setFontSize(9)
          pdf.setFont('helvetica', 'italic')
          currentY = addText(`Target: ${categoryData.weeklyTarget}`, 25, currentY + 3)
          currentY += 2
        }
        
        pdf.setFontSize(10)
        pdf.setFont('helvetica', 'normal')
        if (categoryData.items && Array.isArray(categoryData.items)) {
          categoryData.items.forEach((item: string) => {
            if (currentY > pageHeight - 15) {
              pdf.addPage()
              currentY = 20
            }
            currentY = addText(`• ${item}`, 30, currentY + 3, pageWidth - 60)
          })
        }
        currentY += 6
      })
      
      // Add shopping tips if available
      if (data.mealPlan.shoppingList.shoppingTips) {
        if (currentY > pageHeight - 40) {
          pdf.addPage()
          currentY = 20
        }
        
        pdf.setFontSize(12)
        pdf.setFont('helvetica', 'bold')
        currentY = addText('Shopping Tips:', 25, currentY)
        currentY += 5
        
        pdf.setFontSize(10)
        pdf.setFont('helvetica', 'normal')
        data.mealPlan.shoppingList.shoppingTips.forEach((tip: string) => {
          if (currentY > pageHeight - 15) {
            pdf.addPage()
            currentY = 20
          }
          currentY = addText(`• ${tip}`, 30, currentY, pageWidth - 60)
          currentY += 4
        })
      }
    } else {
      // Legacy structure
      Object.entries(data.mealPlan.shoppingList).forEach(([category, items]: [string, any]) => {
        if (currentY > pageHeight - 30) {
          pdf.addPage()
          currentY = 20
        }
        
        pdf.setFont('helvetica', 'bold')
        currentY = addText(`${category.charAt(0).toUpperCase() + category.slice(1)}:`, 25, currentY)
        
        pdf.setFont('helvetica', 'normal')
        if (Array.isArray(items)) {
          items.forEach(item => {
            currentY = addText(`• ${item}`, 30, currentY + 4)
          })
        }
        currentY += 6
      })
    }
  }
  
  // Tips Section
  if (data.mealPlan?.mealPrepTips) {
    // Check if we need a new page
    if (currentY > pageHeight - 40) {
      pdf.addPage()
      currentY = 20
    }
    
    currentY = addColorfulHeader('MEAL PREP STRATEGY', currentY, [155, 89, 182])
    currentY += 5
    
    // Handle both old array format and new structured format
    if (Array.isArray(data.mealPlan.mealPrepTips)) {
      // Legacy format - simple array
      pdf.setFontSize(11)
      pdf.setFont('helvetica', 'normal')
      data.mealPlan.mealPrepTips.forEach((tip: string) => {
        if (currentY > pageHeight - 20) {
          pdf.addPage()
          currentY = 20
        }
        currentY = addText(`• ${tip}`, 25, currentY, pageWidth - 50)
        currentY += 4
      })
    } else {
      // New structured format
      const tipCategories = ['weekly', 'daily', 'storage', 'goalSpecific', 'timeHacks']
      
      tipCategories.forEach(categoryKey => {
        const category = data.mealPlan.mealPrepTips[categoryKey]
        if (!category || !category.items) return
        
        // Check if we need a new page
        if (currentY > pageHeight - 40) {
          pdf.addPage()
          currentY = 20
        }
        
        // Category header
        pdf.setFontSize(13)
        pdf.setFont('helvetica', 'bold')
        currentY = addText(category.title, 25, currentY, undefined, 13)
        currentY += 5
        
        // Category items
        pdf.setFontSize(10)
        pdf.setFont('helvetica', 'normal')
        category.items.forEach((tip: string) => {
          if (currentY > pageHeight - 15) {
            pdf.addPage()
            currentY = 20
          }
          currentY = addText(`• ${tip}`, 30, currentY, pageWidth - 60)
          currentY += 4
        })
        
        currentY += 6
      })
      
      // Add summary info if available
      if (data.mealPlan.mealPrepTips.weeklyTimeInvestment || data.mealPlan.mealPrepTips.successTip) {
        if (currentY > pageHeight - 30) {
          pdf.addPage()
          currentY = 20
        }
        
        pdf.setFontSize(11)
        pdf.setFont('helvetica', 'bold')
        currentY = addText('Key Insights:', 25, currentY)
        currentY += 4
        
        pdf.setFontSize(10)
        pdf.setFont('helvetica', 'italic')
        
        if (data.mealPlan.mealPrepTips.weeklyTimeInvestment) {
          currentY = addText(`Time Investment: ${data.mealPlan.mealPrepTips.weeklyTimeInvestment}`, 30, currentY, pageWidth - 60)
          currentY += 4
        }
        
        if (data.mealPlan.mealPrepTips.successTip) {
          currentY = addText(`Success Tip: ${data.mealPlan.mealPrepTips.successTip}`, 30, currentY, pageWidth - 60)
          currentY += 4
        }
      }
    }
  }
  
  // Footer on last page
  pdf.setFontSize(9)
  pdf.setFont('helvetica', 'italic')
  pdf.text('This report is generated for informational purposes. Consult with a healthcare professional before making significant dietary changes.', 20, pageHeight - 15, { maxWidth: pageWidth - 40 })
  
  // Save the PDF
  pdf.save(`diet-plan-${new Date().toISOString().split('T')[0]}.pdf`)
}